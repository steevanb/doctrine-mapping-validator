<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Mapping;

class NamedNativeQueryMapping
{
    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $query;

    /** @var ?string */
    protected $resultClass;

    /** @var ?string */
    protected $resultSetMapping;

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

    public function setResultClass(?string $resultClass): self
    {
        $this->resultClass = $resultClass;

        return $this;
    }

    public function getResultClass(): ?string
    {
        return $this->resultClass;
    }

    public function setResultSetMapping(?string $resultSetMapping): self
    {
        $this->resultSetMapping = $resultSetMapping;

        return $this;
    }

    public function getResultSetMapping(): ?string
    {
        return $this->resultSetMapping;
    }
}
