<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Course;
use App\Entity\Grade;
use App\Entity\User;
use App\Repository\GradeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GradeRepositoryTest extends KernelTestCase
{
    private GradeRepository $gradeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->gradeRepository = self::getContainer()->get(GradeRepository::class);
    }

    public function testFindByStudent(): void
    {
        $student = new User();
        $student->setEmail('student@test.com');
        $student->setPassword('hashed');
        $student->setName('Test Student');
        $student->setRoles(['ROLE_STUDENT']);

        $course = new Course();
        $course->setTitle('Test Course');
        $course->setDescription('Description');

        $grade1 = new Grade();
        $grade1->setStudent($student);
        $grade1->setCourse($course);
        $grade1->setValue(18.0);
        $grade1->setType('exam');
        $grade1->setCoefficient(1);

        $grade2 = new Grade();
        $grade2->setStudent($student);
        $grade2->setCourse($course);
        $grade2->setValue(16.5);
        $grade2->setType('homework');
        $grade2->setCoefficient(1);

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($student);
        $em->persist($course);
        $em->persist($grade1);
        $em->persist($grade2);
        $em->flush();

        $grades = $this->gradeRepository->findByStudent($student);

        $this->assertCount(2, $grades);
        $this->assertEquals(18.0, $grades[0]->getValue());
    }

    public function testFindByCourse(): void
    {
        $course = new Course();
        $course->setTitle('Course A');
        $course->setDescription('Description A');

        // Use a unique email for each test run
        $student = new User();
        $student->setEmail('student_' . uniqid() . '@test.com');
        $student->setPassword('hashed');
        $student->setName('Test Student');
        $student->setRoles(['ROLE_STUDENT']);

        $grade = new Grade();
        $grade->setStudent($student);
        $grade->setCourse($course);
        $grade->setValue(17.5);
        $grade->setType('exam');
        $grade->setCoefficient(2);

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($course);
        $em->persist($student);
        $em->persist($grade);
        $em->flush();

        $grades = $this->gradeRepository->findByCourse($course);

        $this->assertCount(1, $grades);
        $this->assertEquals(17.5, $grades[0]->getValue());
    }

    public function testFindByStudentAndCourse(): void
    {
        $student = new User();
        $student->setEmail('student_' . uniqid() . '@test.com');
        $student->setPassword('hashed');
        $student->setName('Test Student 2');
        $student->setRoles(['ROLE_STUDENT']);

        $course = new Course();
        $course->setTitle('Course B');
        $course->setDescription('Description B');

        $grade = new Grade();
        $grade->setStudent($student);
        $grade->setCourse($course);
        $grade->setValue(19.0);
        $grade->setType('project');
        $grade->setCoefficient(3);

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($student);
        $em->persist($course);
        $em->persist($grade);
        $em->flush();

        $grades = $this->gradeRepository->findByStudentAndCourse($student, $course);

        $this->assertCount(1, $grades);
        $this->assertEquals(19.0, $grades[0]->getValue());
    }
}
