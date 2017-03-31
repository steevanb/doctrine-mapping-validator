<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class IndexMapping
{
    /** @var Mapping */
    protected $mapping;

    /** @var ?string */
    protected $name;

    /** @var string[] */
    protected $columns = [];

    /** @var string[] */
    protected $flags;

    /** @var string[] */
    protected $options;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    public function addFlag(string $name): self
    {
        $this->flags[] = $name;

        return $this;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function addOption(IndexOptionMapping $option): self
    {
        $this->options[] = $option;

        return $this;
    }

    /** @return IndexOptionMapping[] */
    public function getOptions(): array
    {
        return $this->options;
    }
}
