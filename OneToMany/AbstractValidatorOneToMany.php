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

    /** @var object */
    protected $leftEntity;

    /** @var string */
    protected $leftEntityClass;

    /** @var string */
    protected $leftEntityProperty;

    /** @var string */
    protected $leftEntityGetter;

    /** @var string */
    protected $leftEntityAdder;

    /** @var string */
    protected $leftEntityRemover;

    /** @var object */
    protected $rightEntity;

    /** @var string */
    protected $rightEntityClass;

    /** @var string */
    protected $rightEntityroperty;

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
        $passedReport = new PassedReport($message);
        $success = true;

        try {
            $this
                ->validateLeftEntityDefaultValue($passedReport)
                ->validateAddRightEntity($passedReport)
                ->validateRemoveRightEntity($passedReport);
        } catch (ReportException $e) {
            $success = false;
        } catch (\Exception $e) {
            $success = false;

            $errorReport = new ErrorReport($e->getMessage());
            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());
            $this->report->addError($errorReport);
        }

        if ($success) {
            $this->report->addPassed($passedReport);
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
        $this->leftEntityGetter = 'get' . ucfirst($this->leftEntityProperty);
        $this->leftEntityAdder = 'add' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntityRemover = 'remove' . ucfirst(substr($this->leftEntityProperty, 0, -1));

        $this->rightEntityClass = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappings()[$this->leftEntityProperty]['targetEntity'];
        $this->rightEntity = $this->createRightEntity();
        $this->rightEntityroperty = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappedByTargetField($this->leftEntityProperty);
        $this->rightEntitySetter = 'set' . ucfirst($this->rightEntityroperty);
        $this->rightEntityGetter = 'get' . ucfirst($this->rightEntityroperty);
    }

    /**
     * @param PassedReport $passedReport
     * @return $this
     * @throws ReportException
     */
    protected function validateLeftEntityDefaultValue(PassedReport $passedReport)
    {
        $this->assertLeftEntityGetterMethodExists();
        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if ($collection instanceof Collection === false) {
            $message = $this->leftEntityClass . '::' . $this->leftEntityGetter . '()';
            $message .= ' must return an instance of ' . Collection::class;
            $errorReport = new ErrorReport($message);

            $help = 'You should call $this->' . '$' . $this->leftEntityProperty;
            $help .= ' = new ' . ArrayCollection::class . '() in ' . $this->leftEntityClass . '::__construct().';
            $errorReport->addHelp($help);

            $errorReport->addLink('http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional');

            throw new ReportException($this->report, $errorReport);
        } else {
            $info = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' is correctly initialized';
            $info .= ' as an instance of ' . Collection::class . '.';
            $passedReport->addInfo($info);
        }

        return $this;
    }

    /**
     * @param PassedReport $passedReport
     * @return $this
     */
    protected function validateAddRightEntity(PassedReport $passedReport)
    {
        $this->addRightEntity($passedReport);
        $this->flushAddRightEntity($passedReport);
        $passedReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);

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
     * @return string
     */
    protected function getLeftEntityAddMethodCall()
    {
        return $this->leftEntityClass . '::' . $this->leftEntityAdder . '()';
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
     */
    protected function assertLeftEntityGetterMethodExists()
    {
        $getterError = 'Method ' . $this->leftEntityClass . '::' . $this->leftEntityGetter . '() does not exist.';
        $getterHelp = 'You must create this method, in order to retrieve ';
        $getterHelp .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
        $this->assertMethodExists($this->leftEntity, $this->leftEntityGetter, $getterError, $getterHelp);

        return $this;
    }

    /**
     * @param PassedReport $passedReport
     * @return $this
     * @throws ReportException
     */
    protected function addRightEntity(PassedReport $passedReport)
    {
        $adderError = ' You must create this method in order to add entity in ';
        $adderError .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
        $this->assertMethodExists($this->leftEntity, $this->leftEntityAdder, $adderError);

        $getterError = ' You must create this method in order to get ' . $this->leftEntityClass;
        $getterError .= ' from ' . $this->rightEntityClass . '.';
        $this->assertMethodExists($this->rightEntity, $this->rightEntityGetter, $getterError);

        call_user_func([ $this->leftEntity, $this->leftEntityAdder ], $this->rightEntity);
        $this->assertRightEntityIsInCollection();

        call_user_func([ $this->leftEntity, $this->leftEntityAdder ], $this->rightEntity);
        $this->assertOnlyOneRightEntityIsInCollection();

        $info = $this->getLeftEntityAddMethodCall() . ' add only one ' . $this->rightEntityClass;
        $info .= ', even with mutiple calls.';
        $passedReport->addInfo($info);

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
            $leftEntityReflection = new \ReflectionClass($this->leftEntity);
            $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() ';
            $message .= 'does not call ' . $this->rightEntityClass . '::' . $this->rightEntitySetter . '($this).';

            $errorReport = new ErrorReport($message);
            $errorReport->addFile($leftEntityReflection->getFileName());
            $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);

            $help = 'As Doctrine use Many side of relations to retrieve informations at update / insert, ';
            $help .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should call ';
            $help .= $this->rightEntityClass . '::' . $this->rightEntitySetter . '($this). Otherwhise, ';
            $help .= $this->rightEntityClass . ' will not be saved with relation to ' . $this->leftEntityClass . '.';
            $errorReport->addHelp($help);

            throw new ReportException($this->report, $errorReport);
        }

        $this->assertLeftEntityGetterMethodExists();

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
            $message = $this->getLeftEntityAddMethodCall() . ' should not add same instance of ';
            $message .= $this->rightEntityClass . ' twice.';
            $errorReport = new ErrorReport($message);
            $errorReport->addMethodCode($this->rightEntity, $this->leftEntityAdder);

            throw new ReportException($this->report, $errorReport);
        }

        return $this;
    }

    /**
     * @param PassedReport $passedReport
     * @return $this
     * @throws ReportException
     */
    protected function flushAddRightEntity(PassedReport $passedReport)
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
        $passedReport->addInfo($info);

        $this->manager->refresh($this->leftEntity);
        $this->manager->refresh($this->rightEntity);
        $this->assertRightEntityIsInCollection();

        $info = $this->rightEntityClass . ' is correctly reloaded in ';
        $info .= $this->leftEntityClass . '::$, ';
        $info .= 'even after calling ' . $emClass . '::refresh() on all tested entities.';
        $passedReport->addInfo($info);

        return $this;
    }

    /**
     * @return $this
     * @param PassedReport $passedReport
     * @throws ReportException
     * @throws \Exception
     */
    protected function validateRemoveRightEntity(PassedReport $passedReport)
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

        $passedReport->addInfo(
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
        $removerError = ' You must create this method in order to remove ' . $this->rightEntityClass . ' in ';
        $removerError .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
        $this->assertMethodExists($this->leftEntity, $this->leftEntityRemover, $removerError);

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
