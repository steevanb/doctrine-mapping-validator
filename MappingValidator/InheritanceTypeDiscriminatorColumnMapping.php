<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class InheritanceTypeDiscriminatorColumnMapping
{
    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $type;

    /** @var ?string */
    protected $length;

    /** @var ?string */
    protected $columnDefinition;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    public function setLength(?string $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): ?string
    {
        return $this->length;
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
}
