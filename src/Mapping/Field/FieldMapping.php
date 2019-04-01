<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Mapping\Field;

class FieldMapping
{
    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $fieldName;

    /** @var ?string */
    protected $type;

    /** @var ?bool */
    protected $id;

    /** @var ?string */
    protected $generatorStrategy;

    /** @var ?bool */
    protected $version;

    /** @var ?bool */
    protected $unique;

    /** @var ?bool */
    protected $nullable;

    /** @var ?string */
    protected $columnName;

    /** @var ?string */
    protected $columnDefinition;

    /** @var ?int */
    protected $length;

    /** @var ?int */
    protected $precision;

    /** @var ?int */
    protected $scale;

    /** @var array */
    protected $options = [];

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setFieldName(?string $fieldName): self
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setId(?bool $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function isId(): ?bool
    {
        return $this->id;
    }

    public function setGeneratorStrategy(?string $generatorStrategy): self
    {
        $this->generatorStrategy = $generatorStrategy;

        return $this;
    }

    public function getGeneratorStrategy(): ?string
    {
        return $this->generatorStrategy;
    }

    public function setVersion(?bool $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function isVersion(): ?bool
    {
        return $this->version;
    }

    public function setUnique(?bool $unique): self
    {
        $this->unique = $unique;

        return $this;
    }

    public function isUnique(): ?bool
    {
        return $this->unique;
    }

    public function setNullable(?bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function isNullable(): ?bool
    {
        return $this->nullable;
    }

    public function setColumnName(?string $columnName): self
    {
        $this->columnName = $columnName;

        return $this;
    }

    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    public function setColumnDefinition(?string $columnDefinition): self
    {
        $this->columnDefinition = $columnDefinition;

        return $this;
    }

    public function getColumnDefinition(): ?string
    {
        return $this->columnDefinition;
    }

    public function setLength(?int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setPrecision(?int $precision): self
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function setScale(?int $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function addOption($name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
