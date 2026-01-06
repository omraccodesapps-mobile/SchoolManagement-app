<?php

namespace App\Controller;

use App\Entity\VideoProgress;
use App\Repository\VideoRepository;
use App\Repository\VideoProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/video-progress', name: 'api_video_progress_')]
class VideoProgressController extends AbstractController
{
    public function __construct(
        private VideoRepository $videoRepository,
        private VideoProgressRepository $progressRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Update video watch progress
     * PUT /api/video-progress/{videoId}
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateProgress(string $videoId, Request $request): JsonResponse
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
            $lastWatchedAt = (float) ($data['lastWatchedAt'] ?? 0);
            $totalWatched = (float) ($data['totalWatched'] ?? 0);
            $percentageWatched = (float) ($data['percentageWatched'] ?? 0);
            $completed = (bool) ($data['completed'] ?? false);

            // Get or create progress record
            $progress = $this->progressRepository->findOrCreate($video, $this->getUser());

            // Update progress
            $progress->setLastWatchedAt($lastWatchedAt);
            $progress->setTotalWatched($totalWatched);
            $progress->setPercentageWatched((string) $percentageWatched);
            $progress->setResumableAt($lastWatchedAt);

            // Mark as completed if reaching 95% or explicitly set
            if ($percentageWatched >= 95 || $completed) {
                $progress->setCompleted(true);
                $progress->setCompletedAt(new \DateTimeImmutable());
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Progress updated successfully',
                'data' => [
                    'videoId' => $video->getId()->toString(),
                    'lastWatchedAt' => $progress->getLastWatchedAt(),
                    'totalWatched' => $progress->getTotalWatched(),
                    'percentageWatched' => (float) $progress->getPercentageWatched(),
                    'completed' => $progress->isCompleted(),
                    'completedAt' => $progress->getCompletedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating progress: ' . $e->getMessage(),
                'error' => 'update_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get video progress for current user
     * GET /api/video-progress/{videoId}
     *
     * @return JsonResponse
     */
    #[Route('/{videoId}', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getProgress(string $videoId): JsonResponse
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

            $progress = $this->progressRepository->findOneBy([
                'video' => $video,
                'student' => $this->getUser(),
            ]);

            if (!$progress) {
                // Return default progress if not started
                return $this->json([
                    'success' => true,
                    'data' => [
                        'videoId' => $video->getId()->toString(),
                        'lastWatchedAt' => 0,
                        'totalWatched' => 0,
                        'percentageWatched' => 0,
                        'completed' => false,
                        'completedAt' => null,
                    ],
                ]);
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'videoId' => $video->getId()->toString(),
                    'lastWatchedAt' => $progress->getLastWatchedAt(),
                    'totalWatched' => $progress->getTotalWatched(),
                    'percentageWatched' => (float) $progress->getPercentageWatched(),
                    'completed' => $progress->isCompleted(),
                    'completedAt' => $progress->getCompletedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching progress: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all completed videos for user
     * GET /api/video-progress/completed-videos
     *
     * @return JsonResponse
     */
    #[Route('/completed-videos', name: 'completed', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCompletedVideos(): JsonResponse
    {
        try {
            $completedVideos = $this->progressRepository->findCompletedByStudent($this->getUser());

            $videoData = array_map(function (VideoProgress $progress) {
                $video = $progress->getVideo();
                return [
                    'videoId' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'course' => $video->getCourse()->getTitle(),
                    'completedAt' => $progress->getCompletedAt()?->format('c'),
                    'percentageWatched' => (float) $progress->getPercentageWatched(),
                ];
            }, $completedVideos);

            return $this->json([
                'success' => true,
                'data' => $videoData,
                'count' => count($videoData),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching completed videos: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get in-progress videos for user (for resume watching)
     * GET /api/video-progress/in-progress-videos
     *
     * @return JsonResponse
     */
    #[Route('/in-progress-videos', name: 'in_progress', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getInProgressVideos(): JsonResponse
    {
        try {
            $inProgressVideos = $this->progressRepository->findInProgressByStudent($this->getUser());

            $videoData = array_map(function (VideoProgress $progress) {
                $video = $progress->getVideo();
                return [
                    'videoId' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'course' => $video->getCourse()->getTitle(),
                    'lastWatchedAt' => $progress->getLastWatchedAt(),
                    'percentageWatched' => (float) $progress->getPercentageWatched(),
                    'resumableAt' => $progress->getResumableAt(),
                ];
            }, $inProgressVideos);

            return $this->json([
                'success' => true,
                'data' => $videoData,
                'count' => count($videoData),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching in-progress videos: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get course progress for current user
     * GET /api/video-progress/course/{courseId}
     *
     * @return JsonResponse
     */
    #[Route('/course/{courseId}', name: 'course_progress', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCourseProgress(string $courseId): JsonResponse
    {
        try {
            $course = $this->videoRepository->getEntityManager()
                ->getRepository('App:Course')
                ->find($courseId);

            if (!$course) {
                return $this->json([
                    'success' => false,
                    'message' => 'Course not found',
                    'error' => 'course_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            $videos = $course->getVideos();
            $totalVideos = count($videos);
            $completedCount = 0;
            $inProgressCount = 0;
            $notStartedCount = 0;
            $averageProgress = 0;
            $totalPercentage = 0;

            $videoProgress = [];
            foreach ($videos as $video) {
                $progress = $this->progressRepository->findOneBy([
                    'video' => $video,
                    'student' => $this->getUser(),
                ]);

                if (!$progress) {
                    $notStartedCount++;
                    $percentage = 0;
                } else {
                    $percentage = (float) $progress->getPercentageWatched();
                    $totalPercentage += $percentage;

                    if ($progress->isCompleted()) {
                        $completedCount++;
                    } else {
                        $inProgressCount++;
                    }
                }

                $videoProgress[] = [
                    'videoId' => $video->getId()->toString(),
                    'title' => $video->getTitle(),
                    'percentageWatched' => $percentage,
                    'completed' => $progress?->isCompleted() ?? false,
                ];
            }

            if ($totalVideos > 0) {
                $averageProgress = $totalPercentage / $totalVideos;
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'courseId' => $courseId,
                    'courseName' => $course->getTitle(),
                    'totalVideos' => $totalVideos,
                    'completedVideos' => $completedCount,
                    'inProgressVideos' => $inProgressCount,
                    'notStartedVideos' => $notStartedCount,
                    'averageProgress' => round($averageProgress, 2),
                    'videos' => $videoProgress,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching course progress: ' . $e->getMessage(),
                'error' => 'fetch_failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
