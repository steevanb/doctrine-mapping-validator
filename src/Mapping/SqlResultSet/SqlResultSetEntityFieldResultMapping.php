<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Mapping\SqlResultSet;

class SqlResultSetEntityFieldResultMapping
{
    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $column;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setColumn(?string $column): self
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }
}
