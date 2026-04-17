<?php

namespace App\Tests\Controller;

use App\Entity\AddressDE;
use App\Entity\CourseDE;
use App\Repository\CountryRepository;
use App\Repository\CourseRepository;
use App\Repository\RegionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseControllerTest extends WebTestCase {
	private function createAuthenticatedClient(string $username = 'super'): \Symfony\Bundle\FrameworkBundle\KernelBrowser {
        $client = static::createClient();
        
        // Create a user with ROLE_SUPER for authentication
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
		$logger = $container->get(LoggerInterface::class);
		$passwordHasher = $container->get(UserPasswordHasherInterface::class);

		// Note: You may need to adjust this based on your User entity
		// This assumes you have a UserDE entity with proper authentication setup
		$userRepository = new UserRepository($em, $logger, $passwordHasher);
		$testUser = $userRepository->findOneBy(['username' => $username]);
        
        if (!$testUser) {
            // Create test user if doesn't exist
            // Adjust based on your User entity structure
            $this->markTestSkipped('Test user with ROLE_SUPER not found. Create a test user first.');
        }
        $client->loginUser($testUser);
        
        return $client;
    }

    /**
     * @throws \Exception
     */
    private function createTestCourse(): CourseDE {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $courseRepository = new CourseRepository($em, $logger);

        $course = new CourseDE();
        $course->setName('Test Course');
        $course->setWebsite('https://testcourse.com');

        $address = new AddressDE();
        $address->setAddressline1('Test Street');
        $address->setAddressline2(null);
        $address->setCity('Test City');
        $regionRepository = new RegionRepository($em, $logger);
        $region = $regionRepository->findOneBy(array('name' => 'Michigan'));
        $address->setRegion($region);

        $course->setAddress($address);

        $courseRepository->saveCourse($course);
        return $course;
    }

	private function deleteTestCourse(CourseDE $course): void {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

		// Re-fetch the entity to ensure it's managed by this EntityManager
		$managedCourse = $em->find(CourseDE::class, $course->getId());

		if ($managedCourse) {
			$em->remove($managedCourse);
			$em->flush();
		}

	}

    public function testListRouteRequiresAuthentication(): void {
        $client = static::createClient();
        $client->request('GET', '/course/list');
        
        // Should redirect to login or return 403
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testListRouteWithAuthentication(): void {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/course/list');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Courses');
    }

    public function testViewRouteWithAuthentication(): void {
        $client = $this->createAuthenticatedClient();
        
        // Get a test course ID from the database
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
	    $logger = $container->get(LoggerInterface::class);
        $courseRepository = new CourseRepository($em, $logger, CourseDE::class);
        $course = $courseRepository->findOneBy([]);
        
        if (!$course) {
            $this->markTestSkipped('No courses found in database for testing.');
        }
        
        $client->request('GET', '/course/view/' . $course->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Course');
    }

    public function testNewCourseGetRequest(): void {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/course/new');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'New Course');
        $this->assertSelectorExists('form');
    }

    public function testNewCoursePostWithValidData(): void {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/course/new');
        
        $testCourseName = 'Test Course ' . time();
		$form = $crawler->selectButton('Generate')->form([
            'form[name]' => $testCourseName,
            'form[numberOfNines]' => '2',
            'form[numberOfTees]' => '3',
        ]);
        
        $client->submit($form);
        
        // Should redirect to course list after successful creation
        $this->assertResponseRedirects('/course/list');
        
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

	    $container = static::getContainer();
	    $em = $container->get(EntityManagerInterface::class);
	    $logger = $container->get(LoggerInterface::class);
	    $courseRepository = new CourseRepository($em, $logger, CourseDE::class);
		$course = $courseRepository->findOneBy(['name' => $testCourseName]);
	    $courseRepository->removeCourse($course);
    }

    public function testNewCourseWithDuplicateName(): void {
	    $client = $this->createAuthenticatedClient();

        $course = $this->createTestCourse();
	    $testCourseName = $course->getName();

        // Get an existing course name
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
	    $logger = $container->get(LoggerInterface::class);
	    $courseRepository = new CourseRepository($em, $logger, CourseDE::class);
        $existingCourse = $courseRepository->findOneBy(['name' => $testCourseName]);
        
        if (!$existingCourse) {
            $this->markTestSkipped('No courses found in database for testing.');
        }
        
        $crawler = $client->request('GET', '/course/new');
        
        $form = $crawler->selectButton('Generate')->form([
            'form[name]' => $existingCourse->getName(),
            'form[numberOfNines]' => '2',
            'form[numberOfTees]' => '3',
        ]);
        
        $client->submit($form);
        
        // Should show error message
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Cannot add a course with the same name');

		$this->deleteTestCourse($course);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRouteRequiresDeleteMethod(): void {
        $client = $this->createAuthenticatedClient();

	    $course = $this->createTestCourse();
	    $testCourseName = $course->getName();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
	    $logger = $container->get(LoggerInterface::class);
	    $courseRepository = new CourseRepository($em, $logger, CourseDE::class);
        $course = $courseRepository->findOneBy(['name' => $testCourseName]);

        if (!$course) {
            $this->markTestSkipped('No courses found in database for testing.');
        }

        // Try GET request - should fail
        $client->request('GET', '/course/delete/' . $course->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);

		$this->deleteTestCourse($course);
    }

    public function testDeleteCourse(): void {
        $client = $this->createAuthenticatedClient();
        
        // Create a test course to delete
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        
        $course = $this->createTestCourse();
        $courseId = $course->getId();
        
        // Delete the course
        $client->request('DELETE', '/course/delete/' . $courseId);
        
        // Should redirect to course list
        $this->assertResponseRedirects('/course/list');
        
        // Verify course was deleted
	    $logger = $container->get(LoggerInterface::class);
	    $courseRepository = new CourseRepository($em, $logger, CourseDE::class);
        $deletedCourse = $courseRepository->find($courseId);
        $this->assertNull($deletedCourse);
    }

    public function testRouteNames(): void {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get('router');
        
        // Test that route names exist and point to correct paths
        $this->assertEquals('/course/list', $router->generate('course_list'));
        $this->assertEquals('/course/new', $router->generate('course_new'));
        $this->assertEquals('/course/view/1', $router->generate('course_view', ['id' => 1]));
        $this->assertEquals('/course/edit/1', $router->generate('course_edit', ['id' => 1]));
        $this->assertEquals('/course/delete/1', $router->generate('course_delete', ['id' => 1]));
    }

    public function testViewNonExistentCourse(): void {
        $client = $this->createAuthenticatedClient();
        
        // Use a very high ID that likely doesn't exist
	    $crawler = $client->request('GET', '/course/view/999999');

	    // Should return 200 with error message
	    $this->assertResponseIsSuccessful();
	    $this->assertSelectorTextContains('.alert-warning', 'Course not found');
    }
}
