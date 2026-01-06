<?php

namespace App\Controller;

use App\Entity\LiveSession;
use App\Entity\LiveChatMessage;
use App\Repository\LiveChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/live-sessions/{liveSessionId}/chat', name: 'api_live_chat_')]
class ChatController extends AbstractController
{
    public function __construct(
        private LiveChatMessageRepository $chatRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'send', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function send(LiveSession $liveSessionId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['message']) || empty(trim($data['message']))) {
                return $this->json(['error' => 'Message cannot be empty'], Response::HTTP_BAD_REQUEST);
            }

            $message = new LiveChatMessage();
            $message->setSession($liveSessionId);
            $message->setSender($this->getUser());
            $message->setMessage(trim($data['message']));

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            return $this->json([
                'id' => $message->getId(),
                'user_id' => $message->getSender()->getId(),
                'user_name' => $message->getSender()->getName(),
                'message' => $message->getMessage(),
                'created_at' => $message->getSentAt()->format('c'),
                'is_answer' => $message->isAnswer(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to send message',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(LiveSession $liveSessionId, Request $request): JsonResponse
    {
        try {
            $page = (int) ($request->query->get('page') ?? 1);
            $limit = (int) ($request->query->get('limit') ?? 50);

            $messages = $this->chatRepository->findBy(
                ['session' => $liveSessionId],
                ['sentAt' => 'ASC'],
                $limit,
                ($page - 1) * $limit
            );

            $formatted = array_map(function (LiveChatMessage $msg) {
                return [
                    'id' => $msg->getId(),
                    'user_id' => $msg->getSender()->getId(),
                    'user_name' => $msg->getSender()->getName(),
                    'message' => $msg->getMessage(),
                    'created_at' => $msg->getSentAt()->format('c'),
                    'is_answer' => $msg->isAnswer(),
                ];
            }, $messages);

            return $this->json([
                'page' => $page,
                'limit' => $limit,
                'total' => count($messages),
                'messages' => $formatted,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch messages',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{messageId}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(LiveSession $liveSessionId, LiveChatMessage $messageId, Request $request): JsonResponse
    {
        try {
            if ($messageId->getSender() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['message']) || empty(trim($data['message']))) {
                return $this->json(['error' => 'Message cannot be empty'], Response::HTTP_BAD_REQUEST);
            }

            $messageId->setMessage(trim($data['message']));
            $this->entityManager->flush();

            return $this->json([
                'id' => $messageId->getId(),
                'message' => $messageId->getMessage(),
                'updated_at' => (new \DateTimeImmutable())->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update message',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{messageId}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(LiveSession $liveSessionId, LiveChatMessage $messageId): JsonResponse
    {
        try {
            if ($messageId->getSender() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($messageId);
            $this->entityManager->flush();

            return $this->json(['message' => 'Message deleted']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete message',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{messageId}/pin', name: 'pin', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function pin(LiveSession $liveSessionId, LiveChatMessage $messageId): JsonResponse
    {
        try {
            if ($liveSessionId->getTeacher() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $messageId->setIsAnswer(!$messageId->isAnswer());
            $this->entityManager->flush();

            return $this->json([
                'id' => $messageId->getId(),
                'is_answer' => $messageId->isAnswer(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to toggle pin',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
