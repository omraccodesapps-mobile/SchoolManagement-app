<?php

namespace App\Controller;

use App\Entity\Video;
use App\Entity\VideoNote;
use App\Repository\VideoRepository;
use App\Repository\VideoNoteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/video-details', name: 'api_video_details_')]
class VideoDetailsController extends AbstractController
{
    public function __construct(
        private VideoRepository $videoRepository,
        private VideoNoteRepository $videoNoteRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Add a note at a specific timestamp
     * POST /api/video-details/{videoId}/notes
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}/notes', name: 'add_note', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addNote(string $videoId, Request $request): JsonResponse
    {
        try {
            $video = $this->videoRepository->find($videoId);
            if (!$video) {
                return $this->json([
                    'success' => false,
                    'message' => 'Video not found',
                    'error' => 'video_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            $content = $data['content'] ?? null;
            $timestamp = (float) ($data['timestamp'] ?? 0);

            if (!$content) {
                return $this->json([
                    'success' => false,
                    'message' => 'Note content is required',
                    'error' => 'content_required',
                ], Response::HTTP_BAD_REQUEST);
            }

            $note = new VideoNote();
            $note->setVideo($video);
            $note->setStudent($this->getUser());
            $note->setContent($content);
            $note->setTimestamp($timestamp);

            $this->entityManager->persist($note);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Note added successfully',
                'data' => [
                    'id' => $note->getId(),
                    'content' => $note->getContent(),
                    'timestamp' => $note->getTimestamp(),
                    'createdAt' => $note->getCreatedAt()?->format('c'),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error adding note: ' . $e->getMessage(),
                'error' => 'add_note_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all notes for a video
     * GET /api/video-details/{videoId}/notes
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}/notes', name: 'get_notes', methods: ['GET'])]
    public function getNotes(string $videoId, Request $request): JsonResponse
    {
        try {
            $video = $this->videoRepository->find($videoId);
            if (!$video) {
                return $this->json([
                    'success' => false,
                    'message' => 'Video not found',
                    'error' => 'video_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Get notes for current user only
            $notes = $this->videoNoteRepository->findBy([
                'video' => $video,
                'student' => $this->getUser(),
            ], ['timestamp' => 'ASC']);

            $noteData = array_map(function (VideoNote $note) {
                return [
                    'id' => $note->getId(),
                    'content' => $note->getContent(),
                    'timestamp' => $note->getTimestamp(),
                    'createdAt' => $note->getCreatedAt()?->format('c'),
                    'updatedAt' => $note->getUpdatedAt()?->format('c'),
                ];
            }, $notes);

            return $this->json([
                'success' => true,
                'data' => $noteData,
                'count' => count($noteData),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching notes: ' . $e->getMessage(),
                'error' => 'fetch_notes_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a note
     * PUT /api/video-details/notes/{noteId}
     *
     * @return JsonResponse
     */
    #[Route('/notes/{noteId}', name: 'update_note', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateNote(int $noteId, Request $request): JsonResponse
    {
        try {
            $note = $this->videoNoteRepository->find($noteId);
            if (!$note) {
                return $this->json([
                    'success' => false,
                    'message' => 'Note not found',
                    'error' => 'note_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Verify ownership
            if ($note->getStudent() !== $this->getUser()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only edit your own notes',
                    'error' => 'unauthorized',
                ], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (isset($data['content'])) {
                $note->setContent($data['content']);
            }
            if (isset($data['timestamp'])) {
                $note->setTimestamp((float) $data['timestamp']);
            }

            $note->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Note updated successfully',
                'data' => [
                    'id' => $note->getId(),
                    'content' => $note->getContent(),
                    'timestamp' => $note->getTimestamp(),
                    'updatedAt' => $note->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating note: ' . $e->getMessage(),
                'error' => 'update_note_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a note
     * DELETE /api/video-details/notes/{noteId}
     *
     * @return JsonResponse
     */
    #[Route('/notes/{noteId}', name: 'delete_note', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteNote(int $noteId): JsonResponse
    {
        try {
            $note = $this->videoNoteRepository->find($noteId);
            if (!$note) {
                return $this->json([
                    'success' => false,
                    'message' => 'Note not found',
                    'error' => 'note_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Verify ownership
            if ($note->getStudent() !== $this->getUser()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only delete your own notes',
                    'error' => 'unauthorized',
                ], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($note);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Note deleted successfully',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting note: ' . $e->getMessage(),
                'error' => 'delete_note_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get video metadata and available qualities
     * GET /api/video-details/{videoId}/metadata
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}/metadata', name: 'metadata', methods: ['GET'])]
    public function getMetadata(string $videoId): JsonResponse
    {
        try {
            $video = $this->videoRepository->find($videoId);
            if (!$video) {
                return $this->json([
                    'success' => false,
                    'message' => 'Video not found',
                    'error' => 'video_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'description' => $video->getDescription(),
                    'duration' => $video->getDuration(),
                    'type' => $video->getType(),
                    'status' => $video->getStatus(),
                    'variants' => $this->getVideoVariants($video),
                    'chapters' => $this->getVideoChapters($video),
                    'uploadedBy' => $video->getUploadedBy()?->getFirstName() . ' ' . $video->getUploadedBy()?->getLastName(),
                    'createdAt' => $video->getCreatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching metadata: ' . $e->getMessage(),
                'error' => 'metadata_fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper: Get video variants
     *
     * @return array
     */
    private function getVideoVariants(Video $video): array
    {
        $variants = [];
        foreach ($video->getVariants() as $variant) {
            $variants[] = [
                'id' => $variant->getId(),
                'resolution' => $variant->getResolution(),
                'bitrate' => $variant->getBitrate(),
                'fileSize' => $variant->getFileSize(),
                'status' => $variant->getStatus(),
            ];
        }
        return $variants;
    }

    /**
     * Helper: Get video chapters
     *
     * @return array
     */
    private function getVideoChapters(Video $video): array
    {
        $chapters = [];
        foreach ($video->getChapters() as $chapter) {
            $chapters[] = [
                'id' => $chapter->getId(),
                'title' => $chapter->getTitle(),
                'startTime' => $chapter->getStartTime(),
                'endTime' => $chapter->getEndTime(),
                'description' => $chapter->getDescription(),
                'orderIndex' => $chapter->getOrderIndex(),
            ];
        }
        return $chapters;
    }
}
