<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Video;
use App\Repository\CourseRepository;
use App\Repository\VideoRepository;
use App\Service\Video\VideoUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/courses', name: 'api_courses_')]
class CourseVideosController extends AbstractController
{
    public function __construct(
        private CourseRepository $courseRepository,
        private VideoRepository $videoRepository,
        private VideoUploadService $uploadService,
    ) {
    }

    /**
     * Get all videos for a course with streaming URLs
     * GET /api/courses/{courseId}/videos
     *
     * @return JsonResponse
     */
    #[Route('/{courseId}/videos', name: 'list_videos', methods: ['GET'])]
    public function getCourseVideos(string $courseId): JsonResponse
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

            $videos = $this->videoRepository->findByCourse($course, 'READY');

            $videoData = array_map(function (Video $video) {
                return [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'description' => $video->getDescription(),
                    'duration' => $video->getDuration(),
                    'type' => $video->getType(),
                    'status' => $video->getStatus(),
                    'thumbnailUrl' => $this->uploadService->getThumbnailUrl($video),
                    'qualities' => $this->uploadService->getAvailableStreams($video),
                    'uploadedBy' => $video->getUploadedBy()?->getName(),
                    'createdAt' => $video->getCreatedAt()?->format('c'),
                ];
            }, $videos);

            return $this->json([
                'success' => true,
                'data' => [
                    'courseId' => $course->getId(),
                    'courseName' => $course->getTitle(),
                    'courseDescription' => $course->getDescription(),
                    'videoCount' => count($videoData),
                    'videos' => $videoData,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching course videos: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all videos for a course including processing status (for teachers)
     * GET /api/courses/{courseId}/videos/all
     *
     * @return JsonResponse
     */
    #[Route('/{courseId}/videos/all', name: 'list_all_videos', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function getAllCourseVideos(string $courseId): JsonResponse
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

            // Verify user is the course teacher
            if ($course->getTeacher() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json([
                    'success' => false,
                    'message' => 'Unauthorized: You are not the teacher of this course',
                    'error' => 'unauthorized',
                ], Response::HTTP_FORBIDDEN);
            }

            $videos = $this->videoRepository->findByCourse($course);

            $videoData = array_map(function (Video $video) {
                $qualities = [];
                if ($video->getStatus() === 'READY') {
                    $qualities = $this->uploadService->getAvailableStreams($video);
                }

                return [
                    'id' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'description' => $video->getDescription(),
                    'duration' => $video->getDuration(),
                    'type' => $video->getType(),
                    'status' => $video->getStatus(),
                    'thumbnailUrl' => $this->uploadService->getThumbnailUrl($video),
                    'qualities' => $qualities,
                    'uploadedBy' => $video->getUploadedBy()?->getName(),
                    'createdAt' => $video->getCreatedAt()?->format('c'),
                    'updatedAt' => $video->getUpdatedAt()?->format('c'),
                ];
            }, $videos);

            return $this->json([
                'success' => true,
                'data' => [
                    'courseId' => $course->getId(),
                    'courseName' => $course->getTitle(),
                    'videoCount' => count($videoData),
                    'readyCount' => count(array_filter($videoData, fn($v) => $v['status'] === 'READY')),
                    'processingCount' => count(array_filter($videoData, fn($v) => $v['status'] === 'PROCESSING')),
                    'videos' => $videoData,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching course videos: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get course summary with video statistics
     * GET /api/courses/{courseId}/summary
     *
     * @return JsonResponse
     */
    #[Route('/{courseId}/summary', name: 'course_summary', methods: ['GET'])]
    public function getCourseSummary(string $courseId): JsonResponse
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
            $readyVideos = $this->videoRepository->findByCourse($course, 'READY');
            $processingVideos = $this->videoRepository->findProcessingVideos();

            $totalDuration = 0;
            foreach ($videos as $video) {
                if ($video->getDuration()) {
                    $totalDuration += $video->getDuration();
                }
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'courseId' => $course->getId(),
                    'courseName' => $course->getTitle(),
                    'courseDescription' => $course->getDescription(),
                    'teacher' => $course->getTeacher()?->getName(),
                    'statistics' => [
                        'totalVideos' => count($videos),
                        'readyVideos' => count($readyVideos),
                        'processingVideos' => count(array_filter($videos, fn($v) => $v->getStatus() === 'PROCESSING')),
                        'draftVideos' => count(array_filter($videos, fn($v) => $v->getStatus() === 'DRAFT')),
                        'totalDuration' => $totalDuration,
                        'averageVideoDuration' => count($videos) > 0 ? round($totalDuration / count($videos), 2) : 0,
                    ],
                    'createdAt' => $course->getCreatedAt()?->format('c'),
                    'updatedAt' => $course->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching course summary: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
