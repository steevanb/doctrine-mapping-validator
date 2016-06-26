<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

abstract class AbstractValidatorOneToMany implements ValidatorOneToManyInterface
{
    use PropertiesTrait;

    /**
     * @return $this
     */
    abstract protected function callValidates();

    /**
     * @param EntityManagerInterface $manager
     * @param string $leftEntityClassName
     * @param string $property
     * @return $this
     */
    abstract protected function init(EntityManagerInterface $manager, $leftEntityClassName, $property);

    /**
     * @param EntityManagerInterface $manager
     * @param string $leftEntityClassName
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $leftEntityClassName, $property)
    {
        $this->init($manager, $leftEntityClassName, $property);

        $success = true;

        try {
            $this->callValidates();
        } catch (ReportException $e) {
            $success = false;
        } catch (\Exception $e) {
            $success = false;

            $errorReport = new ErrorReport($e->getMessage());
            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());
            $this->report->addError($errorReport);
        }

        if ($success) {
            $this->report->addPassed($this->passedReport);
        }

        return $this->report;
    }
}
