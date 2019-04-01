<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Exception;

use steevanb\DoctrineMappingValidator\Mapping\Mapping;

class MappingValueTypeException extends MappingValidatorException
{
    public function __construct(Mapping $mapping, string $name, $value, array $allowedTypes)
    {
        parent::__construct(
            $mapping,
            [
                'Invalid value type "'
                . $this->getValueType($value)
                . '" for "'
                . $name
                . '".'
                . ' Allowed types : '
                . implode(', ', $allowedTypes)
                . '.'
            ]
        );
    }

    protected function getValueType($value): string
    {
        if (is_object($value)) {
            $return = get_class($value);
        } else {
            $return = gettype($value);
        }

        return $return;
    }
}
