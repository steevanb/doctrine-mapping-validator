<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use FigurinesBundle\Entity\Image;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait AddRightObjectOneToManyTrait
{
    /**
     * @return EntityManagerInterface
     */
    abstract public function getManager();

    /**
     * @return object
     */
    abstract public function getLeftObject();

    /**
     * @return string
     */
    abstract public function getLeftObjectProperty();

    /**
     * @return Report
     */
    abstract public function getReport();

    /**
     * @return $this
     */
    protected function validateAddRightObject()
    {
        $rightObject = $this->createRightObject();

        $this->addRightObject($rightObject);
        $this->flushAddRightObject($rightObject);

        return $this;
    }

    /**
     * @return object
     */
    protected function createRightObject()
    {
        $toto = $this
            ->getManager()
            ->getClassMetadata(get_class($this->getLeftObject()));

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
            ->getManager()
            ->getClassMetadata(get_class($this->getLeftObject()))
            ->getAssociationMappedByTargetField($this->getLeftObjectProperty());
    }

    /**
     * @return string
     */
    protected function getLeftObjectAddMethodName()
    {
        return 'add' . ucfirst(substr($this->getLeftObjectProperty(), 0, -1));
    }

    /**
     * @return string
     */
    protected function getRightObjectGetMappedByMethodName()
    {
        return 'get' . ucfirst($this->getRightObjectMappedBy());
    }

    /**
     * @return string
     */
    protected function getLeftObjectPersistErrorMessage()
    {
        $leftObjectMetadata = $this->getManager()->getClassMetadata(get_class($this->getLeftObject()));
        $propertyMetadata = $leftObjectMetadata->associationMappings[$this->getLeftObjectProperty()];
        $message = null;

        if (in_array('persist', $propertyMetadata['cascade']) === false) {
            $message = 'You have to set "cascade: persist" on your mapping';
            $message .= ', or explicitly call ' . get_class($this->getManager()) . '::persist().';
        } else {
            $message .= 'Cascade persist is set on your mapping.';
        }

        return $message;
    }

    /**
     * @param object $object
     * @param string $method
     * @param callable $error
     * @return $this
     * @throws ReportException
     */
    protected function assertMethodExists($object, $method, callable $error)
    {
        if (method_exists($object, $method) === false) {
            $objectClass = get_class($object);
            $objectReflection = new \ReflectionClass($objectClass);

            $message = $objectClass . '::' . $method . '() does not exists.';
            $message .= call_user_func($error);
            $errorReport = new ErrorReport($message);
            $errorReport->addFile($objectReflection->getFileName());

            throw new ReportException($this->getReport(), $errorReport);
        }

        return $this;
    }

    /**
     * @param object $rightObject
     * @throws \Exception
     */
    protected function addRightObject($rightObject)
    {
        $addMethodName = $this->getLeftObjectAddMethodName();

        $this->assertMethodExists($this->getLeftObject(), $addMethodName, function () {
            $message = ' You must create this method in order to add object in ';
            $message .= get_class($this->getLeftObject()) . '::$' . $this->getLeftObjectProperty() . ' collection.';

            return $message;
        });

        $this->assertMethodExists(
            $rightObject,
            $this->getRightObjectGetMappedByMethodName(),
            function () use ($rightObject) {
                $message = ' You must create this method in order to get ' . get_class($this->getLeftObject());
                $message .= ' from ' . get_class($rightObject) . '.';

                return $message;
            }
        );

        $this->getLeftObject()->{$addMethodName}($rightObject);
        if ($rightObject->{$this->getRightObjectGetMappedByMethodName()}() === $this->getLeftObject()) {
            $leftObjectReflection = new \ReflectionClass($this->getLeftObject());
            $message = get_class($this->getLeftObject()) . '::' . $addMethodName . '()';
            $message .= ' does not call ' . get_class($rightObject) . '::$' . $this->getRightObjectMappedBy() . '.';

            $errorReport = new ErrorReport($message);
            $errorReport->addFile($leftObjectReflection->getFileName());
            $errorReport->addMethodCode($this->getLeftObject(), $addMethodName);

            throw new ReportException($this->getReport(), $errorReport);
        }
    }

    /**
     * @param object $rightObject
     * @return $this
     * @throws \Exception
     */
    protected function flushAddRightObject($rightObject)
    {
        try {
            $this->getManager()->flush();
        } catch (ORMInvalidArgumentException $e) {
            $emClass = get_class($this->getManager());

            $message = 'ORMInvalidArgumentException occured after calling ';
            $message .= ' ' . get_class($this->getLeftObject()) . '::' . $this->getLeftObjectAddMethodName() . '(),';
            $message .= ' and then ' . $emClass . '::flush().';
            $message .= "\r\n" . $this->getLeftObjectPersistErrorMessage();
            throw new \Exception($message . "\r\n \r\n" . $e->getMessage());
        }

        if ($rightObject->getId() === null) {
            $emClass = get_class($this->getManager());

            $message = get_class($rightObject) . '::$id is null after calling';
            $message .= ' ' . get_class($this->getLeftObject()) . '::' . $this->getLeftObjectAddMethodName() . '(),';
            $message .= ' and then ' . $emClass . '::flush().';
            $message .= "\r\n" . $this->getLeftObjectPersistErrorMessage();
            throw new \Exception($message);
        }

        return $this;
    }
}
