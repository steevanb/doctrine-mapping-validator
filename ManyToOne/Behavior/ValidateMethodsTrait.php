<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use steevanb\DoctrineMappingValidator\Behavior\ValidateMethodsTrait as CoreValidateMethodsTrait;

trait ValidateMethodsTrait
{
    use CoreValidateMethodsTrait;

    /** @return string */
    abstract protected function getOwningSideProperty();

    /** @return string */
    abstract protected function getOwningSideIdGetter();

    /** @return string */
    abstract protected function getOwningSideSetter();

    /** @return string */
    abstract protected function getOwningSideGetter();

    /** @return string */
    abstract protected function getOwningSideClassName();

    /** @return string */
    abstract protected function getInverseSideClassName();

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return string */
    abstract protected function getInverseSideSetter();

    /** @return string */
    abstract protected function getInverseSideAdder();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /**
     * @return array
     */
    protected function getOwningSideIdGetterMethodValidation()
    {
        return [
            'method' => $this->getOwningSideIdGetter(),
            'parameters' => [],
            'error' => 'You must create this method in order to get ' . $this->getOwningSideClassName() . '::$id'
        ];
    }

    /**
     * @return array
     */
    protected function getOwningSideSetterMethodValidation()
    {
        $error = 'You must create this method in order to set ' . $this->getInverseSideClassName() . ' to ';
        $error .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';

        return [
            'method' => $this->getOwningSideSetter(),
            'parameters' => [$this->getOwningSideProperty() => [$this->getInverseSideClassName(), 'null']],
            'error' => $error
        ];
    }

    /**
     * @return array
     */
    protected function getOwningSideGetterMethodValidation()
    {
        $error = 'You must create this method in order to get ';
        $error .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';

        return [
            'method' => $this->getOwningSideGetter(),
            'parameters' => [],
            'error' => $error
        ];
    }

    /**
     * @return array
     */
    protected function getInverseSideSetterMethodValidation()
    {
        $error = 'You must create this method in order to set ' . $this->getOwningSideClassName() . ' in ';
        $error .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' Collection.';

        return [
            'method' => $this->getInverseSideSetter(),
            'parameters' => [$this->getInverseSideProperty() => [$this->getOwningSideClassName()]],
            'error' => $error
        ];
    }

    /**
     * @return array
     */
    protected function getInverseSideAdderMethodValidation()
    {
        $error = 'You must create this method in order to add ' . $this->getOwningSideClassName() . ' in ';
        $error .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' Collection.';

        return [
            'method' => $this->getInverseSideAdder(),
            'parameters' => [substr($this->getInverseSideProperty(), 0, -1) => [$this->getOwningSideClassName()]],
            'error' => $error
        ];
    }

    /**
     * @return array
     */
    protected function getInverseSideGetterMethodValidation()
    {
        $error = 'You must create this method, in order to get ';
        $error .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' Collection.';

        return [
            'method' => $this->getInverseSideGetter(),
            'parameters' => [],
            'message' => $error
        ];
    }
}
