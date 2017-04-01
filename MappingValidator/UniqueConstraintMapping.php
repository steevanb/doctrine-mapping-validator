<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class UniqueConstraintMapping
{
    /** @var ?string */
    protected $name;

    /** @var string[] */
    protected $columns = [];

    /** @var UniqueConstraintOptionMapping[] */
    protected $options;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function addColumn(string $name): self
    {
        $this->columns[] = $name;

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addOption(UniqueConstraintOptionMapping $option): self
    {
        $this->options[] = $option;

        return $this;
    }

    /** @return UniqueConstraintOptionMapping[] */
    public function getOptions(): array
    {
        return $this->options;
    }
}
