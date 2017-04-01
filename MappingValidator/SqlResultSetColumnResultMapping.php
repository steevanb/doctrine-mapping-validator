<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class SqlResultSetColumnResultMapping
{
    /** @var ?string */
    protected $name;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
