<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

abstract class AbstractValidatorOneToMany implements ValidatorOneToManyInterface
{
    use AddRightObjectOneToManyTrait;

    /** @var EntityManagerInterface */
    protected $manager;

    /** @var object */
    protected $leftObject;

    /** @var string */
    protected $leftObjectProperty;

    /** @var object */
    protected $rightObject;

    /** @var Report */
    protected $report;

    /**
     * @param EntityManagerInterface $manager
     * @return $this
     */
    public function setManager(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @param object $object
     * @return $this
     */
    public function setLeftObject($object)
    {
        $this->leftObject = $object;

        return $this;
    }

    /**
     * @param string $property
     * @return $this
     */
    public function setLeftObjectProperty($property)
    {
        $this->leftObjectProperty = $property;

        return $this;
    }

    /**
     * @param Report $report
     * @return $this
     */
    public function setReport(Report $report)
    {
        $this->report = $report;

        return $this;
    }

    /**
     * @return Report
     */
    public function validate()
    {
        try {
            $this->validateAddRightObject();
            $this->validateRemoveRightObject();
        } catch (ReportException $e) {}

        return $this->report;
    }

    /**
     * @return $this
     * @throws ReportException
     * @throws \Exception
     */
    protected function validateRemoveRightObject()
    {
        $rightObjectClassName = $this->getRightObjectClassName();
        if ($this->rightObject instanceof $rightObjectClassName === false) {
            throw new \Exception('You must set $this->rightObject with an instance of ' . $rightObjectClassName . '.');
        }
        if ($this->rightObject->getId() === null) {
            throw new \Exception('$this->rightObject->getId() should not be null.');
        }

        $this->removeRightObject();

        $rightObjectLinkedObject = call_user_func([ $this->rightObject, $this->getRightObjectGetMappedByMethodName() ]);
        if ($rightObjectLinkedObject === null) {
            $message = get_class($this->leftObject) . '::' . $removeMethodName . '()';
            $message .= ' does not call ' . get_class($this->rightObject) . '::';
            $message .= $this->getRightObjectSetMappedByMethodName() . '(null).';

            $errorReport = new ErrorReport($message);
            $this->addObjectFileNameToErrorReport($this->leftObject, $errorReport);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    protected function removeRightObject()
    {
        $removeMethodName = $this->getRemoveRightObjectMethodName();
        $this->assertMethodExists($this->leftObject, $removeMethodName, function () {
            $message = ' You must create this method in order to remove ' . get_class($this->rightObject) . ' in ';
            $message .= get_class($this->leftObject) . '::$' . $this->leftObjectProperty . ' collection.';

            return $message;
        });

        try {
            call_user_func([ $this->leftObject, $removeMethodName ], $this->rightObject);
        } catch (\Exception $e) {
            $message = get_class($e) . ' occured while calling ';
            $message .= get_class($this->leftObject) . '::' . $removeMethodName. '().';
            $errorReport = new ErrorReport($message);

            $errorReport->addError($e->getMessage());

            $help = 'It can happen if ' . get_class($this->rightObject) . '::';
            $help .= $this->getRightObjectSetMappedByMethodName() . '()';
            $help .= ' does not allow null as first parameter.';
            $help .= ' This is required to remove link between ' . get_class($this->rightObject) . ' and';
            $help .= get_class($this->leftObject) . '.';
            $errorReport->addHelp($help);

            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());

            throw new ReportException($this->report, $errorReport);
        }
    }

    /**
     * @return string
     */
    protected function getRemoveRightObjectMethodName()
    {
        return 'remove' . ucfirst(substr($this->leftObjectProperty, 0, -1));
    }

    /**
     * @return string
     */
    protected function getRightObjectSetMappedByMethodName()
    {
        return 'set' . ucfirst($this->getRightObjectMappedBy());
    }

    /**
     * @return string
     */
    protected function getRightObjectClassName()
    {
        $associationMappings = $this
            ->manager
            ->getClassMetadata(get_class($this->leftObject))
            ->getAssociationMappings();

        return $associationMappings[$this->leftObjectProperty]['targetEntity'];
    }
}
