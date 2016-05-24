<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\PassedReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

abstract class AbstractValidatorOneToMany implements ValidatorOneToManyInterface
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var Report */
    protected $report;

    /** @var PassedReport */
    protected $passedReport;

    /** @var object */
    protected $leftEntity;

    /** @var string */
    protected $leftEntityClass;

    /** @var string */
    protected $leftEntityProperty;

    /** @var string */
    protected $leftEntityAdder;

    /** @var string */
    protected $leftEntitySetter;

    /** @var string */
    protected $leftEntityGetter;

    /** @var string */
    protected $leftEntityRemover;

    /** @var string */
    protected $leftEntityClearer;

    /** @var object */
    protected $rightEntity;

    /** @var string */
    protected $rightEntityClass;

    /** @var string */
    protected $rightEntityProperty;

    /** @var string */
    protected $rightEntityGetter;

    /** @var string */
    protected $rightEntitySetter;

    /**
     * @param EntityManagerInterface $manager
     * @param object $entity
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $entity, $property)
    {
        $this->init($manager, $entity, $property);

        $message = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' : ';
        $message .= 'oneToMany with ' . $this->rightEntityClass;
        $this->passedReport = new PassedReport($message);
        $success = true;

        try {
            $this
                ->validateMethodsExists()
                ->validateLeftEntityDefaultValue()
                ->validateAddRightEntity()
                ->validateRemoveRightEntity();
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

    /**
     * @param EntityManagerInterface $manager
     * @param object $entity
     * @param string $property
     * @return $this;
     */
    protected function init(EntityManagerInterface $manager, $entity, $property)
    {
        $this->report = new Report();
        $this->manager = $manager;

        $this->leftEntity = $entity;
        $this->leftEntityClass = get_class($entity);
        $this->leftEntityProperty = $property;
        $this->leftEntityAdder = 'add' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntitySetter = 'set' . ucfirst($this->leftEntityProperty);
        $this->leftEntityGetter = 'get' . ucfirst($this->leftEntityProperty);
        $this->leftEntityRemover = 'remove' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntityClearer = 'clear' . ucfirst($this->leftEntityProperty);

        $this->rightEntityClass = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappings()[$this->leftEntityProperty]['targetEntity'];
        $this->rightEntity = $this->createRightEntity();
        $this->rightEntityProperty = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappedByTargetField($this->leftEntityProperty);
        $this->rightEntitySetter = 'set' . ucfirst($this->rightEntityProperty);
        $this->rightEntityGetter = 'get' . ucfirst($this->rightEntityProperty);
    }

    /**
     * @return $this
     */
    protected function validateMethodsExists()
    {
        $leftEntityAdder = 'You must create this method in order to add ' . $this->rightEntityClass . ' in ';
        $leftEntityAdder .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntitySetter = 'You must create this method in order to set all ' . $this->rightEntityClass . ' to ';
        $leftEntitySetter .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntityGetter = 'You must create this method, in order to get ';
        $leftEntityGetter .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntityRemover = 'You must create this method in order to remove ' . $this->rightEntityClass . ' in ';
        $leftEntityRemover .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntityClearer = 'You must create this method in order to clear all ' . $this->rightEntityClass . ' in ';
        $leftEntityClearer .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $rightEntitySetter = 'You must create this method in order to set ' . $this->leftEntityClass . ' to ';
        $rightEntitySetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';

        $rightEntityGetter = 'You must create this method in order to get ';
        $rightEntityGetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';

        $methods = [
            [ 'entity' => $this->leftEntity, 'method' => $this->leftEntityAdder, 'message' => $leftEntityAdder ],
            [ 'entity' => $this->leftEntity, 'method' => $this->leftEntitySetter, 'message' => $leftEntitySetter ],
            [ 'entity' => $this->leftEntity, 'method' => $this->leftEntityGetter, 'message' => $leftEntityGetter ],
            [ 'entity' => $this->leftEntity, 'method' => $this->leftEntityRemover, 'message' => $leftEntityRemover ],
            [ 'entity' => $this->leftEntity, 'method' => $this->leftEntityClearer, 'message' => $leftEntityClearer ],
            [ 'entity' => $this->rightEntity, 'method' => $this->rightEntitySetter, 'message' => $rightEntitySetter ],
            [ 'entity' => $this->rightEntity, 'method' => $this->rightEntityGetter, 'message' => $rightEntityGetter ],
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method['entity'], $method['method'], $method['message']);
        }

        return $this;
    }

    /**
     * @param object $entity
     * @param string $method
     * @param string|null $error
     * @param string|null $help
     * @return $this
     * @throws ReportException
     */
    protected function assertMethodExists($entity, $method, $error = null, $help = null)
    {
        if (method_exists($entity, $method) === false) {
            $class = get_class($entity);

            $message = $class . '::' . $method . '() does not exists.';
            $errorReport = new ErrorReport($message);
            if ($error !== null) {
                $errorReport->addError($error);
            }
            if ($help !== null) {
                $errorReport->addHelp($help);
            }
            $this->addEntityFileNameToErrorReport($entity, $errorReport);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateLeftEntityDefaultValue()
    {
        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if ($collection instanceof Collection === false) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityGetter . '()';
            $message .= ' must return an instance of ' . Collection::class;
            $errorReport = new ErrorReport($message);

            $helpCollection = 'You should call $this->$' . $this->leftEntityProperty;
            $helpCollection .= ' = new ' . ArrayCollection::class . '() in ' . $this->leftEntityClass . '::__construct().';
            $errorReport->addHelp($helpCollection);

            $helpReturn = $this->leftEntityClass . '::' . $this->leftEntityGetter . '() should return ';
            $helpReturn .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . '.';
            $errorReport->addHelp($helpReturn);

            $this->addEntityFileNameToErrorReport($this->leftEntity, $errorReport);
            $errorReport->addMethodCode($this->leftEntity, '__construct');
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

            throw new ReportException($this->report, $errorReport);
        } else {
            $info = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' is correctly initialized';
            $info .= ' as an instance of ' . Collection::class . '.';
            $this->passedReport->addInfo($info);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateAddRightEntity()
    {
        $this->addRightEntity();
        $this->flushAddRightEntity();
        $this->passedReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);

        return $this;
    }

    /**
     * @return object
     */
    protected function createRightEntity()
    {
        $entity = new $this->rightEntityClass();

        $classMetadata = $this->manager->getClassMetadata($this->rightEntityClass);
        $identifiers = $classMetadata->getIdentifier();
        foreach ($classMetadata->fieldMappings as $fieldMapping) {
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
                    $entity->{'set' . $fieldMapping['columnName']}($fieldValue);
                }
            }
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function addEntityFileNameToErrorReport($entity, ErrorReport $errorReport)
    {
        $reflection = new \ReflectionClass(get_class($entity));
        $errorReport->addFile($reflection->getFileName());

        return $this;
    }

    /**
     * @return string
     */
    protected function getLeftEntityPersistErrorMessage()
    {
        $propertyMetadata = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->associationMappings[$this->leftEntityProperty];

        if (in_array('persist', $propertyMetadata['cascade']) === false) {
            $message = 'You have to set "cascade: persist" on your mapping';
            $message .= ', or explicitly call ' . get_class($this->manager) . '::persist().';
        } else {
            $message = 'Cascade persist is set on your mapping.';
        }

        return $message;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function addRightEntity()
    {
        call_user_func([ $this->leftEntity, $this->leftEntityAdder ], $this->rightEntity);
        $this->assertRightEntityIsInCollection();

        call_user_func([ $this->leftEntity, $this->leftEntityAdder ], $this->rightEntity);
        $this->assertOnlyOneRightEntityIsInCollection();

        $info = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() add only one ' . $this->rightEntityClass;
        $info .= ', even with mutiple calls.';
        $this->passedReport->addInfo($info);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertRightEntityIsInCollection()
    {
        $mappedBy = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($mappedBy !== $this->leftEntity) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() does not set ';
            $message .= $this->rightEntityClass . '::$' . $this->rightEntityProperty;
            $errorReport = new ErrorReport($message);

            $helpLeftentity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
            $helpLeftentity .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should call ';
            $helpLeftentity .= $this->rightEntityClass . '::' . $this->rightEntitySetter . '($this). Otherwhise, ';
            $helpLeftentity .= $this->rightEntityClass . ' will not be saved with relation to ' . $this->leftEntityClass . '.';
            $errorReport->addHelp($helpLeftentity);

            $helpRightEntity = $this->rightEntityClass . '::' . $this->rightEntitySetter . '() should set ';
            $helpRightEntity .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
            $errorReport->addHelp($helpRightEntity);

            $helpRightEntity = $this->rightEntityClass . '::' . $this->rightEntityGetter . '() should return ';
            $helpRightEntity .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
            $errorReport->addHelp($helpRightEntity);

            $this->addEntityFileNameToErrorReport($this->leftEntity, $errorReport);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);
            $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);
            $errorReport->addMethodCode($this->rightEntity, $this->rightEntityGetter);

            throw new ReportException($this->report, $errorReport);
        }

        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ], $this->rightEntity);
        if ($collection instanceof Collection === false) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityGetter . '() ';
            $message .= 'must return an instance of ' . Collection::class . ', ' . gettype($collection) . ' returned.';
            $errorReport = new ErrorReport($message);
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertOnlyOneRightEntityIsInCollection()
    {
        $countEntities = 0;
        foreach (call_user_func([ $this->leftEntity, $this->leftEntityGetter ], $this->rightEntity) as $entity) {
            if ($entity === $this->rightEntity) {
                $countEntities++;
            }
        }

        if ($countEntities > 1) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should not add same instance of ';
            $message .= $this->rightEntityClass . ' twice.';
            $errorReport = new ErrorReport($message);
            $errorReport->addMethodCode($this->rightEntity, $this->leftEntityAdder);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function flushAddRightEntity()
    {
        $emClass = get_class($this->manager);

        try {
            $this->manager->flush();
        } catch (ORMInvalidArgumentException $e) {
            $message = 'ORMInvalidArgumentException occured after calling ';
            $message .= ' ' . $this->leftEntityClass . '::' . $this->leftEntityAdder . '(),';
            $message .= ' and then ' . $emClass . '::flush().';
            $message .= "\r\n" . $this->getLeftEntityPersistErrorMessage();
            $errorReport = new ErrorReport($message);
            $errorReport->addError($e->getMessage());

            throw new ReportException($this->report, $errorReport);
        }

        if ($this->rightEntity->getId() === null) {
            $message = $this->rightEntityClass . '::$id is null after calling ';
            $message .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '(), ';
            $message .= 'and then ' . $emClass . '::flush().';
            $errorReport = new ErrorReport($message);
            $errorReport->addError($this->getLeftEntityPersistErrorMessage());

            throw new ReportException($this->report, $errorReport);
        }

        $info = $emClass . '::flush() insert ' . $this->rightEntityClass . ' correctly.';
        $this->passedReport->addInfo($info);

        $this->manager->refresh($this->leftEntity);
        $this->manager->refresh($this->rightEntity);
        $this->assertRightEntityIsInCollection();

        $info = $this->rightEntityClass . ' is correctly reloaded in ';
        $info .= $this->leftEntityClass . '::$, ';
        $info .= 'even after calling ' . $emClass . '::refresh() on all tested entities.';
        $this->passedReport->addInfo($info);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     * @throws \Exception
     */
    protected function validateRemoveRightEntity()
    {
        if ($this->rightEntity instanceof $this->rightEntityClass === false) {
            throw new \Exception(
                'You must set $this->rightObject with an instance of ' . $this->rightEntityClass . '.'
            );
        }
        if ($this->rightEntity->getId() === null) {
            throw new \Exception('$this->rightObject->getId() should not be null.');
        }

        $this->removeRightEntity();

        $rightEntityLinkedEntity = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($rightEntityLinkedEntity === null) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '()';
            $message .= ' does not call ' . $this->rightEntityClass . '::';
            $message .= $this->rightEntitySetter . '(null).';

            $errorReport = new ErrorReport($message);
            $this->addEntityFileNameToErrorReport($this->leftEntity, $errorReport);

            throw new ReportException($this->report, $errorReport);
        }

        $this->passedReport->addInfo(
            $this->leftEntityRemover . ' remove ' . $this->rightEntityClass . ' correctly.'
        );

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function removeRightEntity()
    {
        try {
            call_user_func([ $this->leftEntity, $this->leftEntityRemover ], $this->rightEntity);
        } catch (\Exception $e) {
            $message = get_class($e) . ' occured while calling ';
            $message .= $this->leftEntityClass . '::' . $this->leftEntityRemover. '().';
            $errorReport = new ErrorReport($message);

            $errorReport->addError($e->getMessage());

            $help = 'It can happen if ' . $this->rightEntityClass . '::';
            $help .= $this->rightEntitySetter . '()';
            $help .= ' does not allow null as first parameter.';
            $help .= ' This is required to remove link between ' . $this->rightEntityClass . ' and ';
            $help .= $this->leftEntityClass . '.';
            $errorReport->addHelp($help);

            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }
}
