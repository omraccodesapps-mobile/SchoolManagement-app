<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PdfControllerTest extends WebTestCase
{
    private function clearDatabase(EntityManagerInterface $em): void
    {
        $connection = $em->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys=OFF');
            $schemaManager = $connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();

            foreach ($tables as $table) {
                $connection->executeStatement("DELETE FROM $table");
            }
            $connection->executeStatement('PRAGMA foreign_keys=ON');
        }
    }

    public function testStudentBulletinPdfGeneration(): void
    {
        /** @var KernelBrowser $client */
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->clearDatabase($em);

        // Create test student
        $student = new User();
        $student->setEmail('student@test.com');
        $student->setName('Test Student');
        $student->setRoles(['ROLE_STUDENT']);
        $hashedPassword = $passwordHasher->hashPassword($student, 'password');
        $student->setPassword($hashedPassword);

        // Create test teacher
        $teacher = new User();
        $teacher->setEmail('teacher@test.com');
        $teacher->setName('Test Teacher');
        $teacher->setRoles(['ROLE_TEACHER']);
        $hashedPassword = $passwordHasher->hashPassword($teacher, 'password');
        $teacher->setPassword($hashedPassword);

        // Create course
        $course = new Course();
        $course->setTitle('Mathematics');
        $course->setDescription('Math course');
        $course->setTeacher($teacher);

        // Create enrollment
        $enrollment = new Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setCourse($course);
        $enrollment->setEnrolledAt(new \DateTime());

        $em->persist($student);
        $em->persist($teacher);
        $em->persist($course);
        $em->persist($enrollment);
        $em->flush();

        // Refresh to get the ID and reload from DB
        $em->refresh($student);
        $em->refresh($teacher);
        $em->refresh($course);
        $em->refresh($enrollment);

        // Login as student
        $client->loginUser($student);

        // Request PDF
        $client->request('GET', "/pdf/bulletin/{$course->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    public function testStudentBulletinNotEnrolled(): void
    {
        /** @var KernelBrowser $client */
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->clearDatabase($em);

        $student = new User();
        $student->setEmail('student2@test.com');
        $student->setName('Test Student 2');
        $student->setRoles(['ROLE_STUDENT']);
        $hashedPassword = $passwordHasher->hashPassword($student, 'password');
        $student->setPassword($hashedPassword);

        $teacher = new User();
        $teacher->setEmail('teacher2@test.com');
        $teacher->setName('Test Teacher 2');
        $teacher->setRoles(['ROLE_TEACHER']);
        $hashedPassword = $passwordHasher->hashPassword($teacher, 'password');
        $teacher->setPassword($hashedPassword);

        $course = new Course();
        $course->setTitle('Physics');
        $course->setDescription('Physics course');
        $course->setTeacher($teacher);

        $em->persist($student);
        $em->persist($teacher);
        $em->persist($course);
        $em->flush();

        $client->loginUser($student);

        // Try to access PDF without enrollment
        $client->request('GET', "/pdf/bulletin/{$course->getId()}");

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseReportPdfGeneration(): void
    {
        /** @var KernelBrowser $client */
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->clearDatabase($em);

        $teacher = new User();
        $teacher->setEmail('teacher3@test.com');
        $teacher->setName('Test Teacher 3');
        $teacher->setRoles(['ROLE_TEACHER']);
        $hashedPassword = $passwordHasher->hashPassword($teacher, 'password');
        $teacher->setPassword($hashedPassword);

        $course = new Course();
        $course->setTitle('Chemistry');
        $course->setDescription('Chemistry course');
        $course->setTeacher($teacher);

        $em->persist($teacher);
        $em->persist($course);
        $em->flush();

        $client->loginUser($teacher);

        // Request PDF
        $client->request('GET', "/pdf/course-report/{$course->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    public function testCourseReportNotTeacher(): void
    {
        /** @var KernelBrowser $client */
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->clearDatabase($em);

        $student = new User();
        $student->setEmail('student3@test.com');
        $student->setName('Test Student 3');
        $student->setRoles(['ROLE_STUDENT']);
        $hashedPassword = $passwordHasher->hashPassword($student, 'password');
        $student->setPassword($hashedPassword);

        $teacher = new User();
        $teacher->setEmail('teacher4@test.com');
        $teacher->setName('Test Teacher 4');
        $teacher->setRoles(['ROLE_TEACHER']);
        $hashedPassword = $passwordHasher->hashPassword($teacher, 'password');
        $teacher->setPassword($hashedPassword);

        $course = new Course();
        $course->setTitle('Biology');
        $course->setDescription('Biology course');
        $course->setTeacher($teacher);

        $em->persist($student);
        $em->persist($teacher);
        $em->persist($course);
        $em->flush();

        $client->loginUser($student);

        // Try to access course report as student
        $client->request('GET', "/pdf/course-report/{$course->getId()}");

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseReportNotYourCourse(): void
    {
        /** @var KernelBrowser $client */
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->clearDatabase($em);

        $teacher1 = new User();
        $teacher1->setEmail('teacher5@test.com');
        $teacher1->setName('Test Teacher 5');
        $teacher1->setRoles(['ROLE_TEACHER']);
        $hashedPassword = $passwordHasher->hashPassword($teacher1, 'password');
        $teacher1->setPassword($hashedPassword);

        $teacher2 = new User();
        $teacher2->setEmail('teacher6@test.com');
        $teacher2->setName('Test Teacher 6');
        $teacher2->setRoles(['ROLE_TEACHER']);
        $hashedPassword = $passwordHasher->hashPassword($teacher2, 'password');
        $teacher2->setPassword($hashedPassword);

        $course = new Course();
        $course->setTitle('History');
        $course->setDescription('History course');
        $course->setTeacher($teacher1);

        $em->persist($teacher1);
        $em->persist($teacher2);
        $em->persist($course);
        $em->flush();

        $client->loginUser($teacher2);

        // Try to access course report as different teacher
        $client->request('GET', "/pdf/course-report/{$course->getId()}");

        $this->assertResponseStatusCodeSame(403);
    }
}
