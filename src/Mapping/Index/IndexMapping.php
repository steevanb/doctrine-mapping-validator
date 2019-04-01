<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Mapping\Index;

class IndexMapping
{
    /** @var ?string */
    protected $name;

    /** @var string[] */
    protected $columns = [];

    /** @var string[] */
    protected $flags;

    /** @var string[] */
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
