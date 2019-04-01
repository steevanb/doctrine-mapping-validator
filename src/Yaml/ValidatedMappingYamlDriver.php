<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Yaml;

use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use steevanb\DoctrineMappingValidator\{
    Exception\MappingValidatorException,
    MappingValidator
};

class ValidatedMappingYamlDriver extends SimplifiedYamlDriver
{
    protected function loadMappingFile($file): array
    {
        $return = Yaml::parse(file_get_contents($file));
        foreach ($return as $className => $data) {
            $this->validateMapping($file, $className, $data);
        }

        return $return;
    }

    protected function validateMapping(string $file, string $className, array $data): self
    {
        $validator = new MappingValidator();
        $yamlToMapping = new YamlToMapping($file, $className, $data, $validator);
        $validator
            ->addErrors($yamlToMapping->getErrors())
            ->validate($yamlToMapping->createMapping());
        if ($validator->isValid() === false || $validator->hasWarnings()) {
            throw new MappingValidatorException($validator->getMapping(), $validator->getErrorsAndWarnings());
        }

        return $this;
    }
}
