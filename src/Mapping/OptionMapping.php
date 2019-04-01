<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Mapping;

class OptionMapping
{
    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $value;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
