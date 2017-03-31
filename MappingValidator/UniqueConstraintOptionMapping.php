<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class UniqueConstraintOptionMapping
{
    /** @var UniqueConstraintMapping */
    protected $uniqueConstraint;

    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $value;

    public function __construct(UniqueConstraintMapping $uniqueConstraint)
    {
        $this->uniqueConstraint = $uniqueConstraint;
    }

    public function getUniqueConstraint(): UniqueConstraintMapping
    {
        return $this->uniqueConstraint;
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
