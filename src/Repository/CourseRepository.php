<?php
namespace App\Repository;

use App\Entity\AddressDE;
use App\Entity\CourseDE;
use App\Entity\HoleDE;
use App\Entity\NineDE;
use App\Entity\TeeDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the course table.
 */
 class CourseRepository extends AbstractBaseRepository {
	 public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		 parent::__construct($em, $logger, CourseDE::class);
     }

     /**
      * Checks to make sure all address-required fields are set
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $addressData reference $addressData
      */
     protected function checkAddressData(array &$addressData): void {
	     $addressData['addressline1'] ??= '';
	     $addressData['addressline2'] ??= '';
         $addressData['city'] ??= '';
         $addressData['postalcode'] ??= '';
         $addressData['state'] ??= '';
         $addressData['country'] ??= '';
     }

     /**
      * Checks to make sure all course-required fields are set
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $courseData reference $courseData
      */
     protected function checkCourseData(array &$courseData): void {
         $courseData['name'] ??= '';
         $courseData['website'] ??= '';
         $this->checkAddressData($courseData["address"]);
         $this->checkNinesData($courseData["nine"]);
     }

     /**
      * Checks to make sure all hole-required fields are set
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $holeData reference $holeData
      */
     protected function checkHoleData(array &$holeData): void {
         $holeData['name'] ??= '';
         $holeData['holenumber'] ??= '';
         $holeData['par'] ??= '';
         $holeData['handicap'] ??= '';
         $holeData['length'] ??= '';
     }

     /**
      * Checks to make sure all the holes required fields in the collection are set.
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $holesData reference $holesData
      */
     protected function checkHolesData(array &$holesData): void {
         for ($i = 0; $i < sizeof($holesData); $i++) {
             $this->checkHoleData($holesData[$i]);
         }
     }

     /**
      * Checks to make sure all nine required fields are set
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $nineData reference $nineData
      */
     protected function checkNineData(array &$nineData): void {
         $nineData['name'] ??= '';
         $this->checkTeesData($nineData['tee']);
     }

     /**
      * Checks to make sure all the nine required fields in the collection are set.
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $ninesData reference $ninesData
      */
     protected function checkNinesData(array &$ninesData): void {
         for ($i = 0; $i < sizeof($ninesData); $i++) {
             $this->checkNineData($ninesData[$i]);
         }
     }

     /**
      * Checks to make sure all the required fields are set
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $teeData reference $teeData
      */
     protected function checkTeeData(array &$teeData): void {
         $teeData['name'] ??= '';
         $teeData['par'] ??= '';
         $teeData['rating'] ??= '';
         $teeData['length'] ??= '';
         $teeData['slope'] ??= '';
         $this->checkHolesData($teeData['hole']);
     }

     /**
      * Checks to make sure all the tee-required fields in the collection are set.
      * This is also where to perform secondary filtering/sanitization of data
      *
      * @param array $teesData reference $teesData
      */
     protected function checkTeesData(array &$teesData): void {
         for ($i = 0; $i < sizeof($teesData); $i++) {
             $this->checkTeeData($teesData[$i]);
         }
     }

     /**
      * @param int $id of course
      *
      * @return CourseDE
      */
     public function findById(int $id) : CourseDE {
        return $this->findOneBy(array('id' => $id));
    }

	 /**
	  * @param string $name of course
	  *
	  * @return CourseDE|null
	  */
    public function findCourseByName(string $name) : ?CourseDE {
        return $this->findOneBy(array('name' => $name));
    }

	 /**
	  * @param int $leagueId
	  *
	  * @return array list of CourseDEs
	  */
    public function findCoursesByLeagueId(int $leagueId): array {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('course')
            ->from('App\Entity\LeagueDE', 'league')
            ->join('App\Entity\CourseDE', 'course', Join::WITH, 'course member of league.courses')
            ->where($qb->expr()->eq('league.id', ':leagueId'))
            ->setParameter('leagueId', $leagueId);

        $result = $qb->getQuery()->getResult();
        return $result;
    }

	 /**
	  * Deletes a course entity
	  *
	  * @param CourseDE $course
	  *
	  * @return CourseDE
	  * @throws Exception
	  */
	 public function removeCourse(CourseDE $course): CourseDE {
		 try {
			 $this->getEntityManager()->remove($course);
			 $this->getEntityManager()->flush();
		 } catch (Exception $e) {
			 $this->logError(sprintf('Error in the %s method for course [%s]: %s',
			     'CourseRepository::removeCourse', $course->getName(), $e->getMessage()));
			 throw $e;
		 }
		 return $course;
	 }

	 /**
	  * Adds or updates course entity
	  *
	  * @param array $courseData new or modified course data
	  *
	  * @return CourseDE
	  * @throws Exception
	  */
    public function save(array $courseData): CourseDE {
	    $course = $this->findCourseByName($courseData['name']);

	    if ($course) {
		    // There should be no duplicate course names
		    return $course;
	    }

	    $this->checkCourseData($courseData);
        $course = $this->setCourseData($courseData, $course);

        try {
            $this->getEntityManager()->persist($course);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for course [%s]: %s',
		        'CourseRepository::save', $course->getName(), $e->getMessage()));
			throw $e;
        }
        return $course;
    }

	 /**
	  * Adds all course entities
	  *
	  * @param array $coursesData new or modified list of course data
	  *
	  * @return PersistentCollection of Entity\CourseDE
	  * @throws Exception
	  */
    public function saveAll(array $coursesData): PersistentCollection {
        $courses = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\CourseDE'), new ArrayCollection());

        foreach($coursesData as $courseData) {
            $course = $this->save($courseData);
            $courses->add($course);
        }
        return $courses;
    }

	 /**
	  * Adds or updates course entity
	  *
	  * @param CourseDE $course
	  *
	  * @return CourseDE
	  * @throws Exception
	  */
    public function saveCourse(CourseDE $course): CourseDE {
        try {
            $this->getEntityManager()->persist($course);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for course [%s]: %s',
		        'CourseRepository::saveCourse', $course->getName(), $e->getMessage()));
			throw $e;
        }
        return $course;
    }

	 /**
	  * Calls setters to assign $addressData to properties in $address
	  *
	  * @param array $addressData
	  *
	  * @return AddressDE $address
	  */
    protected function setAddressData(array $addressData): AddressDE {
        $address = new AddressDE();

        $address->setAddressline1($addressData['addressline1']);
        $address->setAddressline2($addressData['addressline2']);
        $address->setCity($addressData['city']);
        $address->setPostalcode($addressData['postalcode']);

        $regionRepository = new RegionRepository($this->getEntityManager(), $this->getLogger());
        $region = $regionRepository->findOneBy(array('code' => $addressData['state']));
        $address->setRegion($region);

        return $address;
    }

	 /**
	  * Calls setters to assign $courseData to properties in $course
	  *
	  * @param array $courseData
	  *
	  * @return CourseDE $course
	  */
    protected function setCourseData(array $courseData): CourseDE {
        $course = new CourseDE($this->getEntityManager());

        $course->setName($courseData['name']);
        $course->setWebsite($courseData['website']);
        $course->setAddress($this->setAddressData($courseData['address']));
        $course->setNines($this->setNinesData($courseData['nine'], $course));

        return $course;
    }

	 /**
	  * Calls setters to assign $holeData to properties in $hole
	  *
	  * @param array $holeData
	  * @param TeeDE $tee the TeeDE associated with this HoleDE
	  *
	  * @return HoleDE $hole
	  */
    protected function setHoleData(array $holeData, TeeDE $tee): HoleDE {
        $hole = new HoleDE();

        $hole->setName($holeData['name']);
        $hole->setHandicap($holeData['handicap']);
        $hole->setHolenumber($holeData['holenumber']);
        $hole->setLength($holeData['length']);
        $hole->setPar($holeData['par']);
        $hole->setTee($tee);

        return $hole;
    }

	 /**
	  * @param array $holesData array of hole data
	  * @param TeeDE $tee , the TeeDE associated with this set of HoleDEs
	  *
	  * @return PersistentCollection of HoleDE
	  */
    protected function setHolesData(array $holesData, TeeDE $tee): PersistentCollection {
        $holes = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\HoleDE'), new ArrayCollection());

        foreach($holesData as $holeData) {
            $holes[] = $this->setHoleData($holeData, $tee);
        }
        return $holes;
    }

	 /**
	  * Calls setters to assign $nineData to properties in $nine
	  *
	  * @param array $nineData
	  * @param CourseDE $course , the CourseDE associated with this NineDE
	  *
	  * @return NineDE $nine
	  */
    protected function setNineData(array $nineData, CourseDE $course): NineDE {
		$nine = new NineDE($this->getEntityManager());

        $nine->setName($nineData['name']);
        $nine->setCourse($course);
        $nine->setTees($this->setTeesData($nineData['tee'], $nine));

        return $nine;
    }

	 /**
	  * @param array $ninesData array of nine data
	  * @param CourseDE $course , the CourseDE associated with this set of NineDEs
	  *
	  * @return PersistentCollection of NineDE
	  */
    protected function setNinesData(array $ninesData, CourseDE $course): PersistentCollection {
        $nines = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\NineDE'), new ArrayCollection());

        foreach($ninesData as $nineData) {
            $nines[] = $this->setNineData($nineData, $course);
        }
        return $nines;
    }

	 /**
	  * Calls setters to assign $teeData to properties in $tee
	  *
	  * @param array $teeData
	  * @param NineDE $nine , the NineDE associated with this TeeDE
	  * @param TeeDE|null $tee
	  *
	  * @return TeeDE $tee
	  */
    protected function setTeeData(array $teeData, NineDE $nine, ?TeeDE $tee = NULL): TeeDE {
        $tee ??= new TeeDE($this->getEntityManager());

        $tee->setName($teeData['name']);
        $tee->setLength($teeData['length']);
        $tee->setPar($teeData['par']);
        $tee->setRating($teeData['rating']);
        $tee->setSlope($teeData['slope']);
        $tee->setNine($nine);
        $tee->setHoles($this->setHolesData($teeData['hole'], $tee));

        return $tee;
    }

	 /**
	  * @param array $teesData array of tee data
	  * @param NineDE $nine , the NineDE associated with this set of TeeDEs
	  * @param PersistentCollection|null $tees of Entity\TeeDE
	  *
	  * @return PersistentCollection of Entity\TeeDE
	  */
    protected function setTeesData(array $teesData, NineDE $nine, ?PersistentCollection $tees = NULL): PersistentCollection {
        $tees ??= new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\TeeDE'), new ArrayCollection());

        foreach($teesData as $teeData) {
            $tees[] = $this->setTeeData($teeData, $nine);
        }
        return $tees;
    }

    /**
     * Calls setters to assign $courseData to properties in $course
     * only if values are assigned
     *
     * @param array $courseData
     * @param CourseDE $course
     *
     * @return CourseDE $course
     */
    public function updateData(array $courseData, CourseDE $course): CourseDE {
        if (isset($data['name'])) {
            $course->setName($data['name']);
        }
        return $course;
    }
}
