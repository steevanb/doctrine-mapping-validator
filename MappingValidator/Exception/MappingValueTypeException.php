<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator\Exception;

use steevanb\DoctrineMappingValidator\MappingValidator\Mapping;

class MappingValueTypeException extends MappingValidatorException
{
    public function __construct(Mapping $mapping, string $name, $value, array $allowedTypes)
    {
        $message = 'Wrong value type ' . $this->getValueType($value) . ' for "' . $name . '".';
        $message .= ' Allowed types : ' . implode(', ', $allowedTypes);

        parent::__construct($mapping, $message);
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
