<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\Common\Collections\Collection;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateMethodsParametersTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateMethodsParameters()
    {
        $this
            ->validateLeftEntityMethodsParameters()
            ->validateRigthtEntityMethodsParameters();

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateLeftEntityMethodsParameters()
    {
        $rightEntityParameterName = substr($this->leftEntityProperty, 0, -1);
        $leftEntityParameters = [
            $this->leftEntityAdder => [ $rightEntityParameterName => [ $this->rightEntityClass ] ],
            $this->leftEntitySetter => [ $this->leftEntityProperty => [ Collection::class ] ],
            $this->leftEntityGetter => [],
            $this->leftEntityRemover => [ $rightEntityParameterName => [ $this->rightEntityClass ] ],
            $this->leftEntityClearer => []
        ];
        $methods = [];
        foreach ($leftEntityParameters as $method => $parameters) {
            $methods[] = $method . '()';
            $this
                ->assertMethodParameters($this->leftEntityClass, $method, $parameters);
        }

        $this->addPassedMethodParametersTest($this->leftEntityClass, $methods);

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateRigthtEntityMethodsParameters()
    {
        $rightEntityparameters = [
            $this->rightEntityIdGetter => [],
            $this->rightEntitySetter => [ $this->rightEntityProperty => [ $this->leftEntityClass, 'null' ] ],
            $this->rightEntityGetter => []
        ];
        $methods = [];
        foreach ($rightEntityparameters as $method => $parameters) {
            $methods[] = $method . '()';
            $this
                ->assertMethodParameters($this->rightEntityClass, $method, $parameters);
        }

        $this->addPassedMethodParametersTest($this->rightEntityClass, $methods);

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
        $countParameters = 0;
        $countRequiredParameters = 0;
        foreach ($parameters as $parameter) {
            $countParameters++;
            if (count($parameter) !== 2) {
                $countRequiredParameters++;
            }
        }
        if ($reflection->getMethod($method)->getNumberOfRequiredParameters() !== $countRequiredParameters) {
            $reportError = true;
        } elseif ($reflection->getMethod($method)->getNumberOfParameters() !== $countParameters) {
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
            $this->throwMethodSignatureIsWrong($className, $method, $parameters);
        }

        return $this;
    }

    /**
     * @param string $className
     * @param string $method
     * @param array $parameters
     * @throws ReportException
     */
    protected function throwMethodSignatureIsWrong($className, $method, array $parameters)
    {
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
                    $helpParameters[] = $types[0] . ' $' . $name . ' = ' . $types[1];
                    break;
            }
        }
        $help .= implode(', ', $helpParameters) . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($className, $method);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @param string $className
     * @param array $methods
     * @return $this
     */
    protected function addPassedMethodParametersTest($className, array $methods)
    {
        $message = 'All required methods have a correct signature into ' . $className . ' ';
        $message .= '(' . implode(', ', $methods) . ')';
        $this->passedReport->addTest($this->initializationTestName, $message);

        return $this;
    }
}
