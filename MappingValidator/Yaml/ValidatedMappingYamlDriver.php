<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator\Yaml;

use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use steevanb\DoctrineMappingValidator\MappingValidator\Exception\MappingValidatorException;
use steevanb\DoctrineMappingValidator\MappingValidator\MappingValidator;
use Symfony\Component\Yaml\Yaml;

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
        $yamlToMapping = new YamlToMapping($file, $className, $data);
        $validator = new MappingValidator($yamlToMapping->createMapping());
        $validator
            ->addErrors($yamlToMapping->getErrors())
            ->validate();
        if ($validator->isValid() === false) {
            throw new MappingValidatorException($validator->getMapping(), implode(' ', $validator->getErrors()));
        }

        return $this;
    }
}
