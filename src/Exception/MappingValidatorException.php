<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Exception;

use steevanb\DoctrineMappingValidator\Mapping\Mapping;

class MappingValidatorException extends \Exception
{
    public static $separator = "\n";

    public function __construct(Mapping $mapping, array $errors)
    {
        parent::__construct(
            $mapping->getSource()
            . static::$separator
            . 'Entity: '
            . $mapping->getClassName()
            . static::$separator
            . implode(
                static::$separator,
                array_map(
                    function($error) {
                        static $index = 0;
                        $index++;

                        return '#' . $index . ' ' . $error;
                    },
                    $errors
                )
            )
        );
    }
}
