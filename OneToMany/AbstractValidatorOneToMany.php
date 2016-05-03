<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

abstract class AbstractValidatorOneToMany implements ValidatorOneToManyInterface
{
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
     */
    protected function validateAddRightObject()
    {
        $this->rightObject = $this->createRightObject();

        $this->addRightObject();
        $this->flushAddRightObject();

        return $this;
    }

    /**
     * @return object
     */
    protected function createRightObject()
    {
        $rightObjectClass = $this->getLeftObjectAssociationMapping()['targetEntity'];
        $rightObject = new $rightObjectClass();

        $rightObjectClassMetadata = $this->manager->getClassMetadata($rightObjectClass);
        $identifiers = $rightObjectClassMetadata->getIdentifier();
        foreach ($rightObjectClassMetadata->fieldMappings as $fieldMapping) {
            if (
                in_array($fieldMapping['columnName'], $identifiers) === false
                && (
                    array_key_exists('nullable', $fieldMapping) === false
                    || $fieldMapping['nullable'] === false
                )
            ) {
                $fieldValue = null;
                switch ($fieldMapping['type']) {
                    case 'string' :
                        $fieldValue = uniqid();
                        break;
                    case 'smallint':
                    case 'integer':
                    case 'bigint':
                        $fieldValue = rand(1, 1998);
                        break;
                }
                if ($fieldValue !== null) {
                    $rightObject->{'set' . $fieldMapping['columnName']}($fieldValue);
                }
            }
        }

        return $rightObject;
    }

    /**
     * @return string
     */
    protected function getLeftObjectMappedBy()
    {
        return $this
            ->manager
            ->getClassMetadata(get_class($this->leftObject))
            ->getAssociationMappedByTargetField($this->leftObjectProperty);
    }

    /**
     * @return string
     */
    protected function getLeftObjectAddMethodName()
    {
        return 'add' . ucfirst(substr($this->leftObjectProperty, 0, -1));
    }

    /**
     * @return string
     */
    protected function getRightObjectGetMappedByMethodName()
    {
        return 'get' . ucfirst($this->getLeftObjectMappedBy());
    }

    /**
     * @param object $object
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function addObjectFileNameToErrorReport($object, ErrorReport $errorReport)
    {
        $objectReflection = new \ReflectionClass(get_class($object));
        $errorReport->addFile($objectReflection->getFileName());

        return $this;
    }

    /**
     * @return array
     */
    protected function getLeftObjectAssociationMapping()
    {
        return $this
            ->manager
            ->getClassMetadata(get_class($this->leftObject))
            ->associationMappings[$this->leftObjectProperty];
    }

    /**
     * @return string
     */
    protected function getLeftObjectPersistErrorMessage()
    {
        $propertyMetadata = $this->getLeftObjectAssociationMapping();
        $message = null;

        if (in_array('persist', $propertyMetadata['cascade']) === false) {
            $message = 'You have to set "cascade: persist" on your mapping';
            $message .= ', or explicitly call ' . get_class($this->manager) . '::persist().';
        } else {
            $message .= 'Cascade persist is set on your mapping.';
        }

        return $message;
    }

    /**
     * @param object $object
     * @param string $method
     * @param $error|null $error
     * @param callable|null $help
     * @return $this
     * @throws ReportException
     */
    protected function assertMethodExists($object, $method, callable $error = null, callable $help = null)
    {
        if (method_exists($object, $method) === false) {
            $objectClass = get_class($object);

            $message = $objectClass . '::' . $method . '() does not exists.';
            $errorReport = new ErrorReport($message);
            if (is_callable($error)) {
                $errorReport->addError(call_user_func($error));
            }
            if (is_callable($help)) {
                $errorReport->addHelp(call_user_func($help));
            }
            $this->addObjectFileNameToErrorReport($object, $errorReport);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function addRightObject()
    {
        $addMethodName = $this->getLeftObjectAddMethodName();

        $this->assertMethodExists($this->leftObject, $addMethodName, function () {
            $message = ' You must create this method in order to add object in ';
            $message .= get_class($this->leftObject) . '::$' . $this->leftObjectProperty . ' collection.';

            return $message;
        });

        $rightObject = $this->rightObject;
        $this->assertMethodExists(
            $this->rightObject,
            $this->getRightObjectGetMappedByMethodName(),
            function () use ($rightObject) {
                $message = ' You must create this method in order to get ' . get_class($this->leftObject);
                $message .= ' from ' . get_class($rightObject) . '.';

                return $message;
            }
        );

        call_user_func([ $this->leftObject, $addMethodName ], $this->rightObject);
        $mappedBy = call_user_func([ $this->rightObject, $this->getRightObjectGetMappedByMethodName() ]);
        if ($mappedBy !== $this->leftObject) {
            $leftObjectReflection = new \ReflectionClass($this->leftObject);
            $message = get_class($this->leftObject) . '::' . $addMethodName . '() ';
            $message .= 'does not call ' . get_class($this->rightObject) . '::$' . $this->getLeftObjectMappedBy() . '.';

            $errorReport = new ErrorReport($message);
            $errorReport->addFile($leftObjectReflection->getFileName());
            $errorReport->addMethodCode($this->leftObject, $addMethodName);

            throw new ReportException($this->report, $errorReport);
        }
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function flushAddRightObject()
    {
        try {
            $this->manager->flush();
        } catch (ORMInvalidArgumentException $e) {
            $emClass = get_class($this->manager);

            $message = 'ORMInvalidArgumentException occured after calling ';
            $message .= ' ' . get_class($this->leftObject) . '::' . $this->getLeftObjectAddMethodName() . '(),';
            $message .= ' and then ' . $emClass . '::flush().';
            $message .= "\r\n" . $this->getLeftObjectPersistErrorMessage();
            throw new \Exception($message . "\r\n \r\n" . $e->getMessage());
        }

        if ($this->rightObject->getId() === null) {
            $emClass = get_class($this->manager);

            $message = get_class($this->rightObject) . '::$id is null after calling';
            $message .= ' ' . get_class($this->leftObject) . '::' . $this->getLeftObjectAddMethodName() . '(),';
            $message .= ' and then ' . $emClass . '::flush().';
            $message .= "\r\n" . $this->getLeftObjectPersistErrorMessage();
            throw new \Exception($message);
        }

        $this->leftObject->setEnabled(false);
        $this->rightObject->setComment($this->rightObject->getComment() . ' AAAA');
        $this->manager->refresh($this->leftObject);
        $this->manager->refresh($this->rightObject);

        return $this;
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
            $message = get_class($this->leftObject) . '::' . $this->getRemoveRightObjectMethodName() . '()';
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
        return 'set' . ucfirst($this->getLeftObjectMappedBy());
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
