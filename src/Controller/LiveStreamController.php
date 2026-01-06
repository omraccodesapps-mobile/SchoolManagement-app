<?php

namespace App\Controller;

use App\Entity\LiveSession;
use App\Entity\LiveAttendance;
use App\Entity\Course;
use App\Repository\LiveSessionRepository;
use App\Repository\LiveAttendanceRepository;
use App\Service\Live\LiveStreamingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/live-sessions', name: 'api_live_sessions_')]
class LiveStreamController extends AbstractController
{
    public function __construct(
        private LiveStreamingService $liveStreamingService,
        private LiveSessionRepository $liveSessionRepository,
        private LiveAttendanceRepository $liveAttendanceRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['course_id'], $data['title'], $data['scheduled_at'])) {
                return $this->json([
                    'error' => 'Missing required fields: course_id, title, scheduled_at'
                ], Response::HTTP_BAD_REQUEST);
            }

            /** @var Course $course */
            $course = $this->entityManager->getRepository(Course::class)->find($data['course_id']);
            if (!$course) {
                return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }

            // Verify teacher owns the course
            if ($course->getTeacher() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $session = new LiveSession();
            $session->setTeacher($this->getUser());
            $session->setCourse($course);
            $session->setTitle($data['title']);
            $session->setDescription($data['description'] ?? null);
            $session->setScheduledAt(new \DateTimeImmutable($data['scheduled_at']));

            $errors = $this->validator->validate($session);
            if (count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($session);
            $this->entityManager->flush();

            return $this->json([
                'id' => $session->getId(),
                'title' => $session->getTitle(),
                'status' => $session->getStatus(),
                'scheduled_at' => $session->getScheduledAt()->format('c'),
                'webrtc_room' => $session->getWebrtcRoom(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(LiveSession $session): JsonResponse
    {
        return $this->json([
            'id' => $session->getId(),
            'title' => $session->getTitle(),
            'description' => $session->getDescription(),
            'status' => $session->getStatus(),
            'teacher_id' => $session->getTeacher()->getId(),
            'teacher_name' => $session->getTeacher()->getName(),
            'course_id' => $session->getCourse()->getId(),
            'course_name' => $session->getCourse()->getTitle(),
            'scheduled_at' => $session->getScheduledAt()->format('c'),
            'started_at' => $session->getStartedAt()?->format('c'),
            'ended_at' => $session->getEndedAt()?->format('c'),
            'recording_url' => $session->getRecordingUrl(),
            'webrtc_room' => $session->getWebrtcRoom(),
            'current_participants' => $session->getAttendances()->count(),
            'total_attendees' => $session->getAttendees(),
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_TEACHER')]
    public function update(LiveSession $session, Request $request): JsonResponse
    {
        try {
            if ($session->getTeacher() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            if ($session->getStatus() === 'LIVE') {
                return $this->json(['error' => 'Cannot update active session'], Response::HTTP_CONFLICT);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) {
                $session->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $session->setDescription($data['description']);
            }
            if (isset($data['scheduled_at'])) {
                $session->setScheduledAt(new \DateTimeImmutable($data['scheduled_at']));
            }
            if (isset($data['attendees'])) {
                $session->setAttendees((int) $data['attendees']);
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Session updated successfully']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(LiveSession $session): JsonResponse
    {
        try {
            if ($session->getTeacher() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            if ($session->getStatus() === 'LIVE') {
                return $this->json(['error' => 'Cannot delete active session'], Response::HTTP_CONFLICT);
            }

            $this->entityManager->remove($session);
            $this->entityManager->flush();

            return $this->json(['message' => 'Session deleted successfully']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/start', name: 'start', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function start(LiveSession $session): JsonResponse
    {
        try {
            if ($session->getTeacher() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            if ($session->getStatus() !== 'SCHEDULED') {
                return $this->json(['error' => 'Session is not scheduled'], Response::HTTP_CONFLICT);
            }

            $this->liveStreamingService->startSession($session);

            return $this->json([
                'message' => 'Session started',
                'status' => $session->getStatus(),
                'webrtc_room' => $session->getWebrtcRoom(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to start session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/end', name: 'end', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function end(LiveSession $session): JsonResponse
    {
        try {
            if ($session->getTeacher() !== $this->getUser()) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            if ($session->getStatus() !== 'LIVE') {
                return $this->json(['error' => 'Session is not active'], Response::HTTP_CONFLICT);
            }

            $this->liveStreamingService->endSession($session);

            return $this->json([
                'message' => 'Session ended',
                'status' => $session->getStatus(),
                'recording_url' => $session->getRecordingUrl(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to end session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/participants', name: 'participants', methods: ['GET'])]
    public function participants(LiveSession $session): JsonResponse
    {
        try {
            $attendances = $this->liveAttendanceRepository->findBy(['session' => $session]);

            $participants = array_map(function (LiveAttendance $attendance) {
                return [
                    'id' => $attendance->getId(),
                    'user_id' => $attendance->getStudent()?->getId(),
                    'user_name' => $attendance->getStudent()?->getName(),
                    'user_email' => $attendance->getStudent()?->getEmail(),
                    'joined_at' => $attendance->getJoinedAt()->format('c'),
                    'left_at' => $attendance->getLeftAt()?->format('c'),
                ];
            }, $attendances);

            return $this->json([
                'total' => count($participants),
                'participants' => $participants,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch participants',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/join', name: 'join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(LiveSession $session): JsonResponse
    {
        try {
            // Check if already joined
            $existing = $this->liveAttendanceRepository->findOneBy([
                'session' => $session,
                'user' => $this->getUser(),
                'leftAt' => null
            ]);

            if ($existing) {
                return $this->json(['error' => 'Already joined'], Response::HTTP_CONFLICT);
            }

            $attendance = new LiveAttendance();
            $attendance->setSession($session);
            $attendance->setStudent($this->getUser());
            $attendance->setJoinedAt(new \DateTimeImmutable());

            $this->entityManager->persist($attendance);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Joined session',
                'attendance_id' => $attendance->getId(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to join session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/leave', name: 'leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(LiveSession $session): JsonResponse
    {
        try {
            $attendance = $this->liveAttendanceRepository->findOneBy([
                'session' => $session,
                'user' => $this->getUser(),
                'leftAt' => null
            ]);

            if (!$attendance) {
                return $this->json(['error' => 'Not joined'], Response::HTTP_NOT_FOUND);
            }

            $attendance->setLeftAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return $this->json(['message' => 'Left session']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to leave session',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
