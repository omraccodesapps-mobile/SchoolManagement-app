<?php

namespace App\Controller\Teacher;

use App\Entity\Course;
use App\Entity\Grade;
use App\Form\GradeType;
use App\Repository\GradeRepository;
use App\Service\GradeService;
use App\Service\StatisticService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teacher/grades')]
#[IsGranted('ROLE_TEACHER')]
class GradeController extends AbstractController
{
    public function __construct(
        private GradeRepository $gradeRepository,
        private GradeService $gradeService,
        private StatisticService $statisticService,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * List all grades for courses taught by the teacher.
     */
    #[Route('', name: 'app_grade_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $courses = $user instanceof \App\Entity\User ? $user->getCourses() : [];

        // Get filter parameters
        $courseFilter = $request->query->get('course');
        $typeFilter = $request->query->get('type');
        $sortBy = $request->query->get('sort', 'created');

        // Build grades array
        $grades = [];
        $selectedCourse = null;

        foreach ($courses as $course) {
            $courseGrades = $this->gradeRepository->findByCourse($course);

            // Apply filters
            if ($courseFilter && $course->getId() != $courseFilter) {
                if ($courseFilter == $course->getId()) {
                    $courseGrades = array_filter($courseGrades);
                    $selectedCourse = $course;
                } else {
                    $courseGrades = [];
                }
            }

            if ($typeFilter && !empty($courseGrades)) {
                $courseGrades = array_filter($courseGrades, fn ($g) => $g->getType() === $typeFilter);
            }

            $grades = array_merge($grades, $courseGrades);
        }

        // Sort grades
        usort($grades, match ($sortBy) {
            'student' => fn ($a, $b) => strcmp(
                $a->getStudent()?->getName() ?? $a->getStudent()?->getEmail() ?? '',
                $b->getStudent()?->getName() ?? $b->getStudent()?->getEmail() ?? ''
            ),
            'value' => fn ($a, $b) => $b->getValue() <=> $a->getValue(),
            'type' => fn ($a, $b) => strcmp($a->getType(), $b->getType()),
            default => fn ($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt(),
        });

        return $this->render('teacher/grade/index.html.twig', [
            'grades' => $grades,
            'courses' => $courses,
            'selected_course' => $selectedCourse,
            'filters' => [
                'course' => $courseFilter,
                'type' => $typeFilter,
                'sort' => $sortBy,
            ],
        ]);
    }

    /**
     * Add a new grade to a course.
     */
    #[Route('/add', name: 'app_grade_add', methods: ['GET', 'POST'])]
    #[Route('/course/{courseId}/add', name: 'app_grade_add_course', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        ?int $courseId = null,
    ): Response {
        $course = null;
        if ($courseId && $courseId > 0) {
            $course = $this->em->getRepository(Course::class)->find($courseId);
        }

        // Use Voter-based authorization for consistent permission handling
        if ($course) {
            $this->denyAccessUnlessGranted('ADD', $course);
        }

        $grade = new Grade();
        if ($course) {
            $grade->setCourse($course);
        }

        $form = $this->createForm(GradeType::class, $grade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->gradeService->addGrade(
                $grade->getStudent(),
                $grade->getCourse(),
                $grade->getValue(),
                $grade->getType(),
                $grade->getCoefficient()
            );

            $this->addFlash('success', sprintf(
                'Grade of %.1f added for %s in %s',
                $grade->getValue(),
                $grade->getStudent()->getName() ?? $grade->getStudent()->getEmail(),
                $grade->getCourse()->getTitle()
            ));

            return $this->redirectToRoute('app_grade_index');
        }

        return $this->render('teacher/grade/add.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
        ]);
    }

    /**
     * Edit an existing grade.
     */
    #[Route('/{id}/edit', name: 'app_grade_edit', methods: ['GET', 'POST'])]
    public function edit(
        Grade $grade,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('EDIT', $grade);

        $originalValue = $grade->getValue();
        $form = $this->createForm(GradeType::class, $grade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->gradeService->updateGrade(
                $grade,
                $grade->getValue(),
                $grade->getType(),
                $grade->getCoefficient()
            );

            $this->addFlash('success', sprintf(
                'Grade updated from %.1f to %.1f for %s',
                $originalValue,
                $grade->getValue(),
                $grade->getStudent()->getName() ?? $grade->getStudent()->getEmail()
            ));

            return $this->redirectToRoute('app_grade_index');
        }

        return $this->render('teacher/grade/edit.html.twig', [
            'form' => $form->createView(),
            'grade' => $grade,
        ]);
    }

    /**
     * Delete a grade (POST only).
     */
    #[Route('/{id}/delete', name: 'app_grade_delete', methods: ['POST'])]
    public function delete(
        Grade $grade,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('DELETE', $grade);

        if ($this->isCsrfTokenValid('delete'.$grade->getId(), $request->request->get('_token'))) {
            $studentName = $grade->getStudent()->getName() ?? $grade->getStudent()->getEmail();
            $this->gradeService->deleteGrade($grade);
            $this->addFlash('success', sprintf('Grade deleted for %s', $studentName));
        } else {
            $this->addFlash('error', 'Invalid security token');
        }

        return $this->redirectToRoute('app_grade_index');
    }

    /**
     * View grades for a specific course with statistics.
     */
    #[Route('/course/{id}/view', name: 'app_grade_course_view', methods: ['GET'])]
    public function viewCourse(Course $course): Response
    {
        if ($course->getTeacher() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $grades = $this->gradeRepository->findByCourse($course);
        $ranking = $this->statisticService->getCourseRanking($course);
        $stats = $this->statisticService->getClassStatistics($course);

        return $this->render('teacher/grade/course_view.html.twig', [
            'course' => $course,
            'grades' => $grades,
            'ranking' => $ranking,
            'statistics' => $stats,
        ]);
    }

    /**
     * Bulk download grades for a course (CSV format).
     */
    #[Route('/course/{id}/export', name: 'app_grade_export', methods: ['GET'])]
    public function export(Course $course): Response
    {
        if ($course->getTeacher() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $grades = $this->gradeRepository->findByCourseGroupedByStudent($course);

        // Generate CSV content
        $csv = "Student,Email,Grade Value,Grade Type,Coefficient,Created At\n";
        foreach ($grades as $grade) {
            $csv .= sprintf(
                "%s,%s,%.1f,%s,%d,%s\n",
                $grade->getStudent()->getName() ?? $grade->getStudent()->getEmail(),
                $grade->getStudent()->getEmail(),
                $grade->getValue(),
                $grade->getType(),
                $grade->getCoefficient(),
                $grade->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$course->getTitle().'_grades.csv"');

        return $response;
    }
}
