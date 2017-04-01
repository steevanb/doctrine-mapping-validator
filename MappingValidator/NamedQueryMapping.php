<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class NamedQueryMapping
{
    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $query;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }
}
