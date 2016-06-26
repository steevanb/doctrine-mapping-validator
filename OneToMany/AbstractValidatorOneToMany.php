<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
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

    /** @var string */
    protected $managerClass;

    /** @var Report */
    protected $report;

    /** @var PassedReport */
    protected $passedReport;

    protected $initializationTestName = 'Initialization';

    /** @var object */
    protected $leftEntity;

    /** @var string */
    protected $leftEntityClass;

    /** @var string */
    protected $leftEntityProperty;

    /** @var string */
    protected $leftEntityAdder;

    /** @var string */
    protected $leftEntityAdderTestName;

    /** @var string */
    protected $leftEntitySetter;

    /** @var string */
    protected $leftEntitySetterTestName;

    /** @var string */
    protected $leftEntityGetter;

    /** @var string */
    protected $leftEntityRemover;

    /** @var string */
    protected $leftEntityRemoverTestName;

    /** @var string */
    protected $leftEntityClearer;

    /** @var string */
    protected $leftEntityClearerTestName;

    /** @var object */
    protected $rightEntity;

    /** @var object */
    protected $rightEntity2;

    /** @var string */
    protected $rightEntityClass;

    /** @var string */
    protected $rightEntityProperty;

    /** @var string */
    protected $rightEntityGetter;

    /** @var string */
    protected $rightEntitySetter;

    /** @var string */
    protected $rightEntityIdGetter;

    /**
     * @param EntityManagerInterface $manager
     * @param string $leftEntityClassName
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $leftEntityClassName, $property)
    {
        $this->init($manager, $leftEntityClassName, $property);

        $message = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' : ';
        $message .= 'oneToMany with ' . $this->rightEntityClass;
        $this->passedReport = new PassedReport($message);
        $success = true;

        try {
            $this
                ->validateMethodsExists()
                ->validateMethodsParameters()
                ->validateLeftEntityDefaultValue()
                ->validateAddRightEntity()
                ->validateRemoveRightEntity()
                ->validateSetRightEntities();
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
     * @param string $leftEntityClassName
     * @param string $property
     * @return $this;
     */
    protected function init(EntityManagerInterface $manager, $leftEntityClassName, $property)
    {
        $this->report = new Report();
        $this->manager = $manager;
        $this->managerClass = get_class($manager);

        $this->leftEntityClass = $leftEntityClassName;
        $this->leftEntity = $this->createLeftEntity();
        $this->manager->persist($this->leftEntity);
        $this->leftEntityProperty = $property;
        $this->leftEntityAdder = 'add' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntityAdderTestName = $this->leftEntityClass . '::' . $this->leftEntityAdder . '()';
        $this->leftEntitySetter = 'set' . ucfirst($this->leftEntityProperty);
        $this->leftEntitySetterTestName = $this->leftEntityClass . '::' . $this->leftEntitySetter . '()';
        $this->leftEntityGetter = 'get' . ucfirst($this->leftEntityProperty);
        $this->leftEntityRemover = 'remove' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntityRemoverTestName = $this->leftEntityClass . '::' . $this->leftEntityRemover . '()';
        $this->leftEntityClearer = 'clear' . ucfirst($this->leftEntityProperty);
        $this->leftEntityClearerTestName = $this->leftEntityClass . '::' . $this->leftEntityClearer . '()';

        $this->rightEntityClass = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappings()[$this->leftEntityProperty]['targetEntity'];
        $this->rightEntity = $this->createRightEntity();
        $this->rightEntity2 = $this->createRightEntity();
        $this->rightEntityProperty = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappedByTargetField($this->leftEntityProperty);
        $this->rightEntitySetter = 'set' . ucfirst($this->rightEntityProperty);
        $this->rightEntityGetter = 'get' . ucfirst($this->rightEntityProperty);
        $this->rightEntityIdGetter = 'getId';
    }

    /**
     * @return object
     */
    protected function createLeftEntity()
    {
        $entity = new $this->leftEntityClass();
        $this->defineRandomData($entity);

        return $entity;
    }

    /**
     * @return object
     */
    protected function createRightEntity()
    {
        $entity = new $this->rightEntityClass();
        $this->defineRandomData($entity);

        return $entity;
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

        $methods = [
            [ 'method' => $this->leftEntityAdder, 'message' => $leftEntityAdder ],
            [ 'method' => $this->leftEntitySetter, 'message' => $leftEntitySetter ],
            [ 'method' => $this->leftEntityGetter, 'message' => $leftEntityGetter ],
            [ 'method' => $this->leftEntityRemover, 'message' => $leftEntityRemover ],
            [ 'method' => $this->leftEntityClearer, 'message' => $leftEntityClearer ],
        ];
        $this->assertEntityMethodsExists($this->leftEntity, $methods);

        $rightEntityIdGetter = 'You must create this method in order to get ';
        $rightEntityIdGetter .= $this->rightEntityClass . '::$id';

        $rightEntitySetter = 'You must create this method in order to set ' . $this->leftEntityClass . ' to ';
        $rightEntitySetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';

        $rightEntityGetter = 'You must create this method in order to get ';
        $rightEntityGetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';

        $methods = [
            [ 'method' => $this->rightEntityIdGetter, 'message' => $rightEntityIdGetter ],
            [ 'method' => $this->rightEntitySetter, 'message' => $rightEntitySetter ],
            [ 'method' => $this->rightEntityGetter, 'message' => $rightEntityGetter ]
        ];
        $this->assertEntityMethodsExists($this->rightEntity, $methods);

        return $this;
    }

    /**
     * @param object $entity
     * @param array $methods
     * @return $this
     */
    protected function assertEntityMethodsExists($entity, $methods)
    {
        $methodsExists = [];
        foreach ($methods as $method) {
            $this->assertMethodExists($entity, $method['method'], $method['message']);
            $methodsExists[] = $method['method'] . '()';
            $this->passedReport->addMethodCode($entity, $method['method']);
        }

        $message = 'All required methods exists into ' . get_class($entity);
        $message .= ' (' . implode(', ', $methodsExists) . ').';
        $this->passedReport->addTest($this->initializationTestName, $message);

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
    protected function validateMethodsParameters()
    {
        $rightEntityParameterName = substr($this->leftEntityProperty, 0, -1);
        $leftEntityParameters = [
            $this->leftEntityAdder => [ $rightEntityParameterName => [ $this->rightEntityClass ] ],
            $this->leftEntitySetter => [ $this->leftEntityProperty => [ Collection::class ] ],
            $this->leftEntityGetter => [],
            $this->leftEntityRemover => [ $rightEntityParameterName => [ $this->rightEntityClass ] ],
            $this->leftEntityClearer => []
        ];
        foreach ($leftEntityParameters as $method => $parameters) {
            $this
                ->assertMethodParameters($this->leftEntityClass, $method, $parameters);
        }

        return $this;
    }

    /**
     * @param string $className
     * @param string $method
     * @param array $parameters
     * @return $this
     * @throws ReportException
     */
    protected function assertMethodParameters($className, $method, array $parameters)
    {
        $reportError = false;

        $reflection = new \ReflectionClass($className);
        if ($reflection->getMethod($method)->getNumberOfRequiredParameters() !== count($parameters)) {
            $reportError = true;
        } else {
            $methodParameters = $reflection->getMethod($method)->getParameters();
            $parameterIndex = 0;
            foreach ($parameters as $types) {
                $type = (string)$methodParameters[$parameterIndex]->getType();
                if (in_array($type, $types) === false) {
                    $reportError = true;
                    break;
                }

                $parameterIndex++;
            }
        }

        if ($reportError) {
            $message = $className . '::' . $method . '() signature is wrong.';
            $errorReport = new ErrorReport($message);

            $help = $className . '::' . $method . '() must have at least this ';
            $help .= (count($parameters) === 1) ? 'parameter: ' : 'parameters: ';
            $helpParameters = [];
            foreach ($parameters as $name => $types) {
                switch (count($types)) {
                    case 0:
                        $helpParameters[] = '$' . $name;
                        break;
                    case 1:
                        $helpParameters[] = $types[0] . ' $' . $name;
                        break;
                    case 2:
                        $helpParameters[] = $types[0] . ' $' . $name . ' = ' . $types[2];
                        break;
                }
            }
            $help .= implode(', ', $helpParameters) . '.';
            $errorReport->addHelp($help);

            $errorReport->addMethodCode($className, $method);

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
            $message = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' is correctly initialized';
            $message .= ' as an instance of ' . Collection::class . '.';
            $this->passedReport->addTest($this->initializationTestName, $message);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateAddRightEntity()
    {
        $this
            ->addRightEntity()
            ->flushAddRightEntity();

        return $this;
    }

    /**
     * @param object $entity
     * @return $this
     */
    protected function defineRandomData($entity)
    {
        $classMetadata = $this->manager->getClassMetadata(get_class($entity));
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
                    case 'date':
                    case 'datetime':
                        $fieldValue = new \DateTime();
                        break;
                }
                if ($fieldValue !== null) {
                    $entity->{'set' . $fieldMapping['columnName']}($fieldValue);
                }
            }
        }

        return $this;
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
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function addLeftEntityPersistError(ErrorReport $errorReport)
    {
        $propertyMetadata = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->associationMappings[$this->leftEntityProperty];

        if (in_array('persist', $propertyMetadata['cascade']) === false) {
            $help = 'You have to set "cascade: persist" on your mapping, ';
            $help .= 'or explicitly call ' . $this->managerClass . '::persist().';
            $errorReport->addHelp($help);
        }

        return $this;
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

        $message = 'Add only one ' . $this->rightEntityClass . ', even with mutiple calls with same instance.';
        $this->passedReport->addTest($this->leftEntityAdderTestName, $message);

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

        $isInCollection = false;
        foreach ($collection as $entity) {
            if ($entity === $this->rightEntity) {
                $isInCollection = true;
                break;
            }
        }
        if ($isInCollection === false) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() ';
            $message .= 'does not add ' . $this->rightEntityClass . '.';
            $errorReport = new ErrorReport($message);

            $help = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should add ';
            $help .= $this->rightEntityClass . ' in ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . '.';
            $errorReport->addHelp($help);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);
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
            $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() ';
            $message .= 'should not add same ' . $this->rightEntityClass . ' instance twice.';
            $errorReport = new ErrorReport($message);

            $help = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should use ';
            $help .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . '->contains().';
            $errorReport->addHelp($help);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

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
        try {
            $this->manager->flush();
        } catch (ORMInvalidArgumentException $e) {
            $message = 'ORMInvalidArgumentException occured after calling ';
            $message .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '(), ';
            $message .= 'then ' . $this->managerClass . '::flush().';
            $errorReport = new ErrorReport($message);

            $errorReport->addError($e->getMessage());
            $this->addLeftEntityPersistError($errorReport);

            throw new ReportException($this->report, $errorReport);
        }

        if ($this->rightEntity->getId() === null) {
            $message = $this->rightEntityClass . '::$id is null after calling ';
            $message .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '(), ';
            $message .= 'then ' . $this->managerClass . '::flush().';
            $errorReport = new ErrorReport($message);

            $errorReport->addMethodCode($this->rightEntity, 'getId');
            $this->addLeftEntityPersistError($errorReport);

            throw new ReportException($this->report, $errorReport);
        }

        $message = $this->managerClass . '::flush() ';
        $message .= 'save ' . $this->leftEntityClass . ' and ' . $this->rightEntityClass . ' correctly.';
        $this->passedReport->addTest($this->leftEntityAdderTestName, $message);

        $this->manager->refresh($this->leftEntity);
        $this->manager->refresh($this->rightEntity);
        $this->assertRightEntityIsInCollection();

        $message = $this->rightEntityClass . ' is correctly reloaded in ';
        $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ', ';
        $message .= 'even after calling ' . $this->managerClass . '::refresh() on all tested entities.';
        $this->passedReport->addTest($this->leftEntityAdderTestName, $message);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     * @throws \Exception
     */
    protected function validateRemoveRightEntity()
    {
        if ($this->rightEntity->getId() === null) {
            throw new \Exception('$this->rightObject->getId() should not be null.');
        }

        $this
            ->removeRightEntity()
            ->assertRightEntityIsNotInCollection()
            ->assertRightEntityLinkIsNull()
            ->flushRemoveRightEntity();

        $this->manager->refresh($this->leftEntity);

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
        } catch (\Throwable $e) {
            $message = get_class($e) . ' occured while calling ';
            $message .= $this->leftEntityClass . '::' . $this->leftEntityRemover. '().';
            $errorReport = new ErrorReport($message);

            $errorReport->addError($e->getMessage());

            $help = 'It can happen if ' . $this->rightEntityClass . '::';
            $help .= $this->rightEntitySetter . '() does not allow null as first parameter.';
            $help .= ' This is required to remove link between ' . $this->rightEntityClass . ' and ';
            $help .= $this->leftEntityClass . '.';
            $errorReport->addHelp($help);

            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
            $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertRightEntityLinkIsNull()
    {
        $rightEntityLinkedEntity = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($rightEntityLinkedEntity !== null) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityRemover . '()';
            $message .= ' does not call ' . $this->rightEntityClass . '::';
            $message .= $this->rightEntitySetter . '(null).';
            $errorReport = new ErrorReport($message);

            $helpLeftentity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
            $helpLeftentity .= $this->leftEntityClass . '::' . $this->leftEntityRemover . '() should call ';
            $helpLeftentity .= $this->rightEntityClass . '::' . $this->rightEntitySetter . '(null). Otherwhise, ';
            $helpLeftentity .= $this->rightEntityClass . ' will not be removed by your manager.';
            $errorReport->addHelp($helpLeftentity);

            $this->addEntityFileNameToErrorReport($this->leftEntity, $errorReport);
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);

            $this->addEntityFileNameToErrorReport($this->rightEntity, $errorReport);
            $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);

            throw new ReportException($this->report, $errorReport);
        }

        $message = 'Set ' . $this->rightEntityClass . '::$' . $this->rightEntityProperty . ' to null.';
        $this->passedReport->addTest($this->leftEntityRemoverTestName, $message);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertRightEntityIsNotInCollection()
    {
        foreach (call_user_func([ $this->leftEntity, $this->leftEntityGetter ]) as $entity) {
            if ($entity === $this->rightEntity) {
                $message = $this->leftEntityClass . '::' . $this->leftEntityRemover . '() should remove ';
                $message .= $this->rightEntityClass . ' in ';
                $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
                $errorReport = new ErrorReport($message);

                $help = 'You should call $this->' . $this->leftEntityProperty . '->removeElement() ';
                $help .= 'in ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '()';
                $errorReport->addHelp($help);

                $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
                $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

                throw new ReportException($this->report, $errorReport);
            }
        }

        $message = 'Remove ' . $this->rightEntityClass;
        $message .= ' in ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' correctly.';
        $this->passedReport->addTest($this->leftEntityRemoverTestName, $message);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function flushRemoveRightEntity()
    {
        $rightEntityId = call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]);
        if ($rightEntityId === null) {
            $message = $this->rightEntityClass . '::' . $this->rightEntityIdGetter . '() should return integer.';
            $errorReport = new ErrorReport($message);

            $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);

            throw new ReportException($this->report, $errorReport);
        }

        try {
            $this->manager->flush();
        } catch (NotNullConstraintViolationException $e) {
            $message = get_class($e) . ' occurend while removing ' . $this->rightEntityClass . '.';
            $errorReport = new ErrorReport($message);

            $help = 'You have to set "orphanRemoval: true" on your mapping, ';
            $help .= 'or explicitly call ' . $this->managerClass . '::remove().';
            $errorReport->addHelp($help);

            $errorReport->addError($e->getMessage());
            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);

            throw new ReportException($this->report, $errorReport);
        }

        if (call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]) !== null) {
            $message = $this->rightEntityClass . '::' . $this->rightEntityIdGetter . '() should return null ';
            $message .= 'after calling ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '() ';
            $message .= 'and ' . $this->managerClass . '::flush().';
            $errorReport = new ErrorReport($message);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
            $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);

            throw new ReportException($this->report, $errorReport);
        }

        if ($this->manager->contains($this->rightEntity)) {
            $message = '$this->rightEntity should not be managed, ';
            $message .= 'after calling ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '() ';
            $message .= 'and ' . $this->managerClass . '::flush().';
            $errorReport = new ErrorReport($message);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);

            throw new ReportException($this->report, $errorReport);
        }

        if ($this->getRightEntityById($rightEntityId) !== null) {
            $message = '$this->rightEntity should not be managed, ';
            $message .= 'after calling ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '() ';
            $message .= 'and ' . $this->managerClass . '::flush().';
            $errorReport = new ErrorReport($message);

            throw new ReportException($this->report, $errorReport);
        }

        $message = $this->managerClass . '::flush() remove ' . $this->rightEntityClass . ' correctly.';
        $this->passedReport->addTest($this->leftEntityRemoverTestName, $message);

        return $this;
    }

    /**
     * @param int $id
     * @return object|null
     */
    protected function getRightEntityById($id)
    {
        return $this->manager->getRepository($this->rightEntityClass)->findOneBy([ 'id' => $id ]);
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateSetRightEntities()
    {
        $this->assertSetRightEntities();

        return $this;
    }

    protected function assertSetRightEntities()
    {
        call_user_func([ $this->rightEntity, $this->rightEntitySetter], null);
        call_user_func([ $this->rightEntity2, $this->rightEntitySetter], null);

        $rightEntities = new ArrayCollection([ $this->rightEntity, $this->rightEntity2 ]);
        call_user_func([ $this->leftEntity, $this->leftEntitySetter ], $rightEntities);
        // try to call setter 2 times, to be sure it clear rightEntites before adding
        call_user_func([ $this->leftEntity, $this->leftEntitySetter ], $rightEntities);

        $settedRightEnties = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if (
            count($settedRightEnties) !== 2
            || $settedRightEnties[0] !== $this->rightEntity
            || $settedRightEnties[1] !== $this->rightEntity2
        ) {
            $message = $this->leftEntityClass . '::' . $this->leftEntitySetter . '() doest not set ';
            $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty;
            $errorReport = new ErrorReport($message);

            $help = 'This method should call $this->' . $this->leftEntityClearer . '(), and ';
            $help .= $this->leftEntityAdder . '() for each ' . $this->rightEntityClass . ' passed.';
            $errorReport->addHelp($help);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntitySetter);

            throw new ReportException($this->report, $errorReport);
        }

        if (
            call_user_func([ $this->rightEntity, $this->rightEntityGetter ]) !== $this->leftEntity
            || call_user_func([ $this->rightEntity2, $this->rightEntityGetter ]) !== $this->leftEntity
        ) {
            $message = $this->leftEntityClass . '::' . $this->leftEntitySetter . '() doest not set ';
            $message .= $this->rightEntityClass . '::$' . $this->rightEntityProperty;
            $errorReport = new ErrorReport($message);

            $help = 'This method should call $this->' . $this->leftEntityClearer . '(), and ';
            $help .= $this->leftEntityAdder . '() for each ' . $this->rightEntityClass . ' passed.';
            $errorReport->addHelp($help);

            $errorReport->addMethodCode($this->leftEntity, $this->leftEntitySetter);

            throw new ReportException($this->report, $errorReport);
        }

        $message = 'Set ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' correctly, ';
        $message .= 'even with multiple calls.';
        $this->passedReport->addTest($this->leftEntitySetterTestName, $message);

        return $this;
    }
}
