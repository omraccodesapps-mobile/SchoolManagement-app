<?php

namespace App\Controller\Teacher;

use App\Entity\Course;
use App\Entity\Video;
use App\Form\CourseType;
use App\Messenger\Message\ProcessVideoMessage;
use App\Repository\CourseRepository;
use App\Service\Video\VideoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teacher/courses')]
#[IsGranted('ROLE_TEACHER')]
class CourseController extends AbstractController
{
    public function __construct(
        private VideoUploadService $videoUploadService,
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $courses = $courseRepository->findByTeacher($this->getUser());

        return $this->render('teacher/course/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $course->setTeacher($this->getUser());
                $em->persist($course);
                $em->flush();

                // Handle video upload if provided
                /** @var UploadedFile|null $videoFile */
                $videoFile = $form->get('video')->getData();

                if ($videoFile) {
                    $videoTitle = $course->getTitle() . ' - Introduction';

                    // Upload the video using VideoUploadService
                    $video = $this->videoUploadService->uploadVideo(
                        $videoFile,
                        $course,
                        $this->getUser(),
                        $videoTitle,
                        'Introduction video for ' . $course->getTitle()
                    );

                    // Dispatch processing message for background video processing
                    $this->messageBus->dispatch(new ProcessVideoMessage($video->getId()));

                    $this->addFlash('success', 'Course created successfully! Video is being processed.');
                } else {
                    $this->addFlash('success', 'Course created successfully!');
                }

                return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating course: ' . $e->getMessage());
                return $this->redirectToRoute('app_course_new');
            }
        }

        return $this->render('teacher/course/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $course);

        return $this->render('teacher/course/show.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $course);

        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Course updated successfully!');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        return $this->render('teacher/course/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $course);

        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $em->remove($course);
            $em->flush();
            $this->addFlash('success', 'Course deleted successfully!');
        }

        return $this->redirectToRoute('app_course_index');
    }
}
