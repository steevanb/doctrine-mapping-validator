<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\ORMInvalidArgumentException;
use FigurinesBundle\Entity\Image;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait AddRightObjectOneToManyTrait
{
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
        $toto = $this
            ->manager
            ->getClassMetadata(get_class($this->leftObject));

        $image = new Image();
        $image->setUrl('http://www.test.com');

        return $image;
    }

    /**
     * @return string
     */
    protected function getRightObjectMappedBy()
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
        return 'get' . ucfirst($this->getRightObjectMappedBy());
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
     * @return string
     */
    protected function getLeftObjectPersistErrorMessage()
    {
        $leftObjectMetadata = $this->manager->getClassMetadata(get_class($this->leftObject));
        $propertyMetadata = $leftObjectMetadata->associationMappings[$this->leftObjectProperty];
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
     * @return $this;
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

        $this->leftObject->{$addMethodName}($this->rightObject);
        if ($this->rightObject->{$this->getRightObjectGetMappedByMethodName()}() !== $this->leftObject) {
            $leftObjectReflection = new \ReflectionClass($this->leftObject);
            $message = get_class($this->leftObject) . '::' . $addMethodName . '()';
            $message .= ' does not call ' . get_class($this->rightObject) . '::$' . $this->getRightObjectMappedBy() . '.';

            $errorReport = new ErrorReport($message);
            $errorReport->addFile($leftObjectReflection->getFileName());
            $errorReport->addMethodCode($this->leftObject, $addMethodName);

            throw new ReportException($this->getReport(), $errorReport);
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

        return $this;
    }
}
