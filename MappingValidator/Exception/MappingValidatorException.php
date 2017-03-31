<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator\Exception;

use steevanb\DoctrineMappingValidator\MappingValidator\Mapping;

class MappingValidatorException extends \Exception
{
    public function __construct(Mapping $mapping, string $message)
    {
        parent::__construct('[' . $mapping->getSource() . '#' . $mapping->getClassName() . '] ' . $message);
    }
}
