<?php
namespace App\Form;

class NewCourseFormBean {
    private string $name;
    private int $numberOfNines;
    private int $numberOfTees;

	/**
	 * @return string name
	 */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return int numberOfNines
     */
    public function getNumberOfNines(): int {
        return $this->numberOfNines;
    }

    /**
     * @return int numberOfTees
     */
    public function getNumberOfTees(): int {
        return $this->numberOfTees;
    }

    /**
     * @param string $name
     */
    public function setName( string $name): void {
        $this->name = $name;
    }

    /**
     * @param int $numberOfNines
     */
    public function setNumberOfNines( int $numberOfNines): void {
        $this->numberOfNines = $numberOfNines;
    }

    /**
     * @param int $numberOfTees
     */
    public function setNumberOfTees( int $numberOfTees) {
        $this->numberOfTees = $numberOfTees;
    }

}