<?php

namespace steevanb\DoctrineMappingValidator\Behavior;

trait IsPhpCodeEqualsToTemplateTrait
{
    use GetMethodCodeTrait;

    /**
     * @param string $className
     * @param string $method
     * @param string $template
     * @param array $vars
     * @return bool
     * @throws \Exception
     */
    protected function isPhpCodeEqualsToTemplate($className, $method, $template, array $vars = [])
    {
        return $this->getPhpCodeFromTemplate($template, $vars) === rtrim($this->getMethodCode($className, $method));
    }

    /**
     * @param string $template
     * @param array $vars
     * @return string
     * @throws \Exception
     */
    protected function getPhpCodeFromTemplate($template, array $vars = [])
    {
        if (is_readable($template) === false) {
            throw new \Exception('Template "' . $template . '" is not readable.');
        }

        $return = rtrim(file_get_contents($template));
        foreach ($vars as $name => $value) {
            $return = str_replace('{{ ' . $name . ' }}', $value, $return);
        }

        return $return;
    }
}
