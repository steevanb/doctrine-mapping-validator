<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class MappingValidator
{
    /** @var Mapping */
    protected $mapping;

    /** @var string[] */
    protected $errors = [];

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function validate(): self
    {
        $this
            ->validateType()
            ->validateCache();

        return $this;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function isValid(): ?bool
    {
        return count($this->getErrors()) === 0;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }

    public function addErrors(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function validateType(): self
    {
        if ($this->getMapping()->getType() === 'mappedSuperclass' && is_bool($this->getMapping()->getReadOnly())) {
            $this->addError('ReadOnly must not be defined for mappedSuperclass type.');
        } elseif ($this->getMapping()->getType() === 'embeddable') {
            if (is_bool($this->getMapping()->getReadOnly())) {
                $this->addError('ReadOnly must not be defined for embeddable type.');
            }
            if ($this->getMapping()->getRepositoryClass() !== null) {
                $this->addError('RepositoryClass must not be defined for embeddable type.');
            }
        } elseif (in_array($this->getMapping()->getType(), ['entity', 'mappedSuperclass', 'embeddable']) === false) {
            $this->addUnkonwMappingError(
                'type',
                $this->getMapping()->getType(),
                ['entity', 'mappedSuperclass', 'embeddable']
            );
        }

        return $this;
    }

    protected function validateCache(): self
    {
        $allowedUsage = ['READ_ONLY', 'NONSTRICT_READ_WRITE', 'READ_WRITE'];
        if (
            $this->getMapping()->getCache()->getUsage() !== null
            && in_array($this->getMapping()->getCache()->getUsage(), $allowedUsage) === false
        ) {
            $this->addUnkonwMappingError(
                'cache.usage',
                $this->getMapping()->getCache()->getUsage(),
                $allowedUsage
            );
        }

        return $this;
    }

    protected function validateNamedQueries()
    {
        foreach ($this->getMapping()->getNamedQueries() as $namedQuery) {
            if ($namedQuery->getName() === null) {

            }
        }
    }

    protected function addUnkonwMappingError(string $name, $value, array $allowedValues): self
    {
        if (is_string($value)) {
            $valueAsString = $value;
        } elseif (is_object($value)) {
            $valueAsString = get_class($value) . '#' . spl_object_hash($value);
        } else {
            $valueAsString = var_export($value, true);
        }

        $this->addError(
            'Unknow mapping value "' . $valueAsString . '" for "' . $name . '".'
            . ' Allowed values: ' . implode(', ', $allowedValues) . '.'
        );

        return $this;
    }
}
