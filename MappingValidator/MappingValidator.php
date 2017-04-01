<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

use Doctrine\ORM\Mapping\NamingStrategy;

class MappingValidator
{
    /** @var ?Mapping */
    protected $mapping;

    /** @var string[] */
    protected $errors = [];

    /** @var NamingStrategy */
    protected $namingStrategy;

    protected $allowedFieldTypes = [
        'string', 'text', 'blob',
        'integer', 'smallint', 'bigint', 'decimal', 'float',
        'date', 'time', 'datetime', 'datetimetz',
        'array', 'simple_array', 'json_array',
        'boolean', 'object', 'guid'
    ];

    public function getMapping(): ?Mapping
    {
        return $this->mapping;
    }

    public function setNamingStrategy(string $namingStrategy): self
    {
        $this->namingStrategy = $namingStrategy;

        return $this;
    }

    public function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }

    public function setAllowedFieldTypes(array $allowedFieldTypes): self
    {
        $this->allowedFieldTypes = $allowedFieldTypes;

        return $this;
    }

    public function addAllowedFieldType(string $name): self
    {
        if (in_array($name, $this->allowedFieldTypes) === false) {
            $this->allowedFieldTypes[] = $name;
        }

        return $this;
    }

    public function getAllowedFieldTypes(): array
    {
        return $this->allowedFieldTypes;
    }

    public function validate(Mapping $mapping): self
    {
        $this->mapping = $mapping;
        $this->errors = [];

        $this
            ->validateClassname()
            ->validateType()
            ->validateCache()
            ->validateNamedQueries()
            ->validateNamedNativeQueries()
            ->validateInheritanceType()
            ->validateDiscriminatorColumn()
            ->validateDiscriminatorMap()
            ->validateChangeTrackingPolicy();

        return $this;
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

    protected function validateClassname(): self
    {
        if (class_exists($this->getMapping()->getClassName()) === false) {
            $this->addError('Entity class name "' . $this->getMapping()->getClassName() . '" does not exist.');
        }

        return $this;
    }

    protected function validateType(): self
    {
        if ($this->getMapping()->getType() === 'mappedSuperclass' && is_bool($this->getMapping()->getReadOnly())) {
            $this->addError('ReadOnly must not be defined for "mappedSuperclass" type.');
        } elseif ($this->getMapping()->getType() === 'embeddable') {
            if (is_bool($this->getMapping()->getReadOnly())) {
                $this->addError('ReadOnly must not be defined for "embeddable" type.');
            }
            if ($this->getMapping()->getRepositoryClass() !== null) {
                $this->addError('RepositoryClass must not be defined for "embeddable" type.');
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

    protected function validateNamedQueries(): self
    {
        $queryNames = [];
        foreach ($this->getMapping()->getNamedQueries() as $index => $query) {
            $name = $query->getName();
            if ($query->getName() === null) {
                $name = '#' . $index;
                $this->addError('Named query ' . $name . ' should have a name.');
            } else {
                if (in_array($query->getName(), $queryNames)) {
                    $this->addError('Named query "' . $name . '" should be defined once.');
                }
                $queryNames[] = $query->getName();
            }
            if ($query->getQuery() === null) {
                $this->addError('Named query "' . $name . '" should have a query string.');
            }
        }

        return $this;
    }

    protected function validateNamedNativeQueries(): self
    {
        $queryNames = [];
        foreach ($this->getMapping()->getNamedNativeQueries() as $index => $query) {
            $name = $query->getName();
            if ($query->getName() === null) {
                $name = '#' . $index;
                $this->addError('Named native query ' . $name . ' should have a name.');
            } else {
                if (in_array($query->getName(), $queryNames)) {
                    $this->addError('Named native query "' . $name . '" should be defined once.');
                }
                $queryNames[] = $query->getName();
            }
            if ($query->getQuery() === null) {
                $this->addError('Named native query "' . $name . ' should have a query string.');
            }
            if ($query->getResultClass() === null && $query->getResultSetMapping() === null) {
                $this->addError('Named native query "' . $name . '" should have a result class or result set mapping.');
            }
            if ($query->getResultClass() !== null) {
                if (class_exists($query->getResultClass()) === false) {
                    $this->addError(
                        'Named native query "' . $name . '" result class "' . $query->getResultClass() . '" not found.'
                    );
                }
            }
            if ($query->getResultSetMapping() !== null) {
                if (in_array($this->getMapping()->getType(), ['entity', 'mappedSuperclass']) === false) {
                    $this->addError(
                        'Named native query "' . $name . '" should not define resultClass. '
                        . 'Only "entity" and "mappedSuperclass" type could.'
                    );
                } else {
                    $sqlResultSetMappingExists = false;
                    $allowedResultSetMappings = [];
                    foreach ($this->getMapping()->getSqlResultSetMappings() as $sqlResultSetMapping) {
                        if ($sqlResultSetMapping->getName() !== null) {
                            $allowedResultSetMappings[] = $sqlResultSetMapping->getName();
                        }
                        if ($sqlResultSetMapping->getName() === $query->getResultSetMapping()) {
                            $sqlResultSetMappingExists = true;
                        }
                    }
                    if ($sqlResultSetMappingExists === false) {
                        $allowedResultSetMappingsMessage = count($allowedResultSetMappings) === 0
                            ? 'No allowed result set mapping found.'
                            : 'Allowed result set mappings : ' . implode(', ', $allowedResultSetMappings) . '.';
                        $this->addError(
                            'Named native query "' . $name . '" result set mapping '
                            . '"' . $query->getResultSetMapping() . '" does not exist. '
                            . $allowedResultSetMappingsMessage
                        );
                    }
                }
            }
        }

        return $this;
    }

    protected function validateInheritanceType(): self
    {
        $allowedValues = ['NONE', 'JOINED', 'SINGLE_TABLE', 'TABLE_PER_CLASS'];
        if (
            $this->getMapping()->getInheritanceType() !== null
            && in_array($this->getMapping()->getInheritanceType(), $allowedValues) === false
        ) {
            $this->addError(
                'Inheritance type "' . $this->getMapping()->getInheritanceType() . '" not found. '
                . 'Allowed values : null, ' . implode(', ', $allowedValues) . '.'
            );
        }

        return $this;
    }

    protected function validateDiscriminatorColumn(): self
    {

        if (
            in_array($this->getMapping()->getInheritanceType(), [null, 'NONE'])
            && (
                $this->getMapping()->getDiscriminatorColumn()->getName() !== null
                || $this->getMapping()->getDiscriminatorColumn()->getType() !== null
                || $this->getMapping()->getDiscriminatorColumn()->getColumnDefinition() !== null
                || $this->getMapping()->getDiscriminatorColumn()->getLength() !== null
            )
        ) {
            $this->addError('Discriminator column should not be defined for inheritance type "NONE".');
        }
        if ($this->getMapping()->getDiscriminatorColumn()->getType() !== null) {
            $this->validateFieldType($this->getMapping()->getDiscriminatorColumn()->getType(), 'discriminatorMap.type');
        }

        return $this;
    }

    protected function validateDiscriminatorMap(): self
    {
        if (
            in_array($this->getMapping()->getInheritanceType(), [null, 'NONE'])
            && count($this->getMapping()->getDiscriminatorMaps()) > 0
        ) {
            $this->addError('Discriminator map should not be defined for inheritance type "NONE".');
        }

        foreach ($this->getMapping()->getDiscriminatorMaps() as $index => $map) {
            if ($map->getName() === null) {
                $this->addError('Discriminator map "#' . $index . '" should have a name.');
            } else {
                // validate $map->getName() is not already defined in fields
            }
            if ($map->getClassName() === null) {
                $this->addError(
                    'Discriminator map "' . ($map->getName() ?? '[No name]') . '" '
                    . 'class name should be defined.'
                );
            } elseif (class_exists($map->getClassName()) === false) {
                $this->addError(
                    'Discriminator map "' . ($map->getName() ?? '[No name]') . '" '
                    . 'class name "' . $map->getClassName() . '" does not exist.');
            }
        }

        return $this;
    }

    protected function validateChangeTrackingPolicy(): self
    {
        $allowedValues = ['DEFERRED_IMPLICIT', 'DEFERRED_EXPLICIT', 'NOTIFY'];
        if (
            $this->getMapping()->getChangeTrackingPolicy() !== null
            && in_array($this->getMapping()->getChangeTrackingPolicy(), $allowedValues) === false
        ) {
            $this->addUnkonwMappingError(
                'changeTrackingPolicy',
                $this->getMapping()->getChangeTrackingPolicy(),
                $allowedValues
            );
        }

        return $this;
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

    protected function validateFieldType(string $type, string $configurationName): self
    {
        if (in_array($type, $this->getAllowedFieldTypes()) === false) {
            $this->addError('Unkonw field type "' . $type . '" for "' . $configurationName . '".');
        }

        return $this;
    }
}
