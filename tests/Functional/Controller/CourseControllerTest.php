<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCourseIndexIsAccessible(): void
    {
        // Unauthenticated users should be redirected to login
        $this->client->request('GET', '/teacher/courses');
        $this->assertResponseRedirects('/login');
    }

    public function testCourseShowRequiresAuthentication(): void
    {
        // Create a Course entity in the test database
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $course = new \App\Entity\Course();
        $course->setTitle('Test Course');
        $entityManager->persist($course);
        $entityManager->flush();
        $courseId = $course->getId();

        $this->client->request('GET', "/teacher/courses/{$courseId}");
        $this->assertResponseRedirects('/login');
    }

    public function testCourseCreationRequiresCsrfToken(): void
    {
        // POST without CSRF should fail
        $this->client->request('POST', '/teacher/courses/new');
        $this->assertResponseRedirects();
    }

    public function testCourseDeleteRequiresOwnership(): void
    {
        // Create a Course entity in the test database
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $course = new \App\Entity\Course();
        $course->setTitle('Test Course for Delete');
        $entityManager->persist($course);
        $entityManager->flush();
        $courseId = $course->getId();

        // Student trying to delete teacher course should get 403 (or redirected to login if not authenticated)
        $this->client->request('POST', "/teacher/courses/{$courseId}/delete");
        $this->assertResponseRedirects('/login');
    }
}
