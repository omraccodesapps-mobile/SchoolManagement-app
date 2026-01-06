<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Video;
use App\Messenger\ProcessVideoMessage;
use App\Repository\CourseRepository;
use App\Repository\VideoRepository;
use App\Service\Video\VideoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/videos', name: 'api_videos_')]
class VideoUploadController extends AbstractController
{
    public function __construct(
        private VideoUploadService $uploadService,
        private VideoRepository $videoRepository,
        private CourseRepository $courseRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Upload a video file for a course
     * POST /api/videos/upload
     *
     * @return JsonResponse
     */
    #[Route('/upload', name: 'upload', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function uploadVideo(Request $request): JsonResponse
    {
        try {
            $courseId = $request->request->get('course_id');
            $title = $request->request->get('title', 'Untitled Video');
            $description = $request->request->get('description', '');

            // Get the uploaded file
            /** @var UploadedFile $file */
            $file = $request->files->get('video');

            if (!$file) {
                return $this->json([
                    'success' => false,
                    'message' => 'No file uploaded',
                    'error' => 'video_file_required',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate course exists
            $course = $this->courseRepository->find($courseId);
            if (!$course) {
                return $this->json([
                    'success' => false,
                    'message' => 'Course not found',
                    'error' => 'course_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Verify user is the course teacher
            if ($course->getTeacher() !== $this->getUser()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Unauthorized: You are not the teacher of this course',
                    'error' => 'unauthorized',
                ], Response::HTTP_FORBIDDEN);
            }

            // Upload the video
            $video = $this->uploadService->uploadVideo(
                $file,
                $course,
                $this->getUser(),
                $title,
                $description
            );

            // Dispatch processing message
            $this->messageBus->dispatch(new ProcessVideoMessage($video->getId()));

            return $this->json([
                'success' => true,
                'message' => 'Video uploaded successfully and queued for processing',
                'data' => [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'status' => $video->getStatus(),
                    'duration' => $video->getDuration(),
                    'courseId' => $video->getCourse()->getId(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error uploading video: ' . $e->getMessage(),
                'error' => 'upload_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all videos for a course
     * GET /api/videos/course/{courseId}
     *
     * @return JsonResponse
     */
    #[Route('/course/{courseId}', name: 'list_by_course', methods: ['GET'])]
    public function listByCourse(string $courseId): JsonResponse
    {
        try {
            $course = $this->courseRepository->find($courseId);
            if (!$course) {
                return $this->json([
                    'success' => false,
                    'message' => 'Course not found',
                    'error' => 'course_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            $videos = $this->videoRepository->findByCourse($course);

            $videoData = array_map(function (Video $video) {
                return [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'description' => $video->getDescription(),
                    'status' => $video->getStatus(),
                    'duration' => $video->getDuration(),
                    'type' => $video->getType(),
                    'createdAt' => $video->getCreatedAt()?->format('c'),
                    'updatedAt' => $video->getUpdatedAt()?->format('c'),
                ];
            }, $videos);

            return $this->json([
                'success' => true,
                'data' => $videoData,
                'count' => count($videoData),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching videos: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a single video by ID
     * GET /api/videos/{videoId}
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}', name: 'get', methods: ['GET'])]
    public function getVideo(string $videoId): JsonResponse
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

            // Get available streaming qualities
            $qualities = $this->uploadService->getAvailableStreams($video);

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'description' => $video->getDescription(),
                    'status' => $video->getStatus(),
                    'duration' => $video->getDuration(),
                    'type' => $video->getType(),
                    'thumbnailUrl' => $this->uploadService->getThumbnailUrl($video),
                    'qualities' => $qualities,
                    'uploadedBy' => $video->getUploadedBy()?->getFirstName() . ' ' . $video->getUploadedBy()?->getLastName(),
                    'courseId' => $video->getCourse()->getId(),
                    'createdAt' => $video->getCreatedAt()?->format('c'),
                    'updatedAt' => $video->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching video: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a video
     * DELETE /api/videos/{videoId}
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_TEACHER')]
    public function deleteVideo(string $videoId): JsonResponse
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

            // Verify user is the video uploader or has admin rights
            if ($video->getUploadedBy() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only delete your own videos',
                    'error' => 'unauthorized',
                ], Response::HTTP_FORBIDDEN);
            }

            $this->uploadService->deleteVideo($video);

            return $this->json([
                'success' => true,
                'message' => 'Video deleted successfully',
                'data' => [
                    'id' => $video->getId()->toString(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting video: ' . $e->getMessage(),
                'error' => 'delete_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get video upload status/progress
     * GET /api/videos/{videoId}/status
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}/status', name: 'status', methods: ['GET'])]
    public function getVideoStatus(string $videoId): JsonResponse
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

            $progress = $this->uploadService->getUploadProgress($video);

            return $this->json([
                'success' => true,
                'data' => $progress,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching video status: ' . $e->getMessage(),
                'error' => 'status_fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search videos by title
     * GET /api/videos/search?q=query&course_id=courseId
     *
     * @return JsonResponse
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function searchVideos(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            $courseId = $request->query->get('course_id');

            if (empty($query)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Search query required',
                    'error' => 'query_required',
                ], Response::HTTP_BAD_REQUEST);
            }

            $course = null;
            if ($courseId) {
                $course = $this->courseRepository->find($courseId);
                if (!$course) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Course not found',
                        'error' => 'course_not_found',
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            $videos = $this->videoRepository->searchByTitle($query, $course);

            $videoData = array_map(function (Video $video) {
                return [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'description' => $video->getDescription(),
                    'status' => $video->getStatus(),
                    'duration' => $video->getDuration(),
                    'courseId' => $video->getCourse()->getId(),
                ];
            }, $videos);

            return $this->json([
                'success' => true,
                'data' => $videoData,
                'count' => count($videoData),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error searching videos: ' . $e->getMessage(),
                'error' => 'search_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
