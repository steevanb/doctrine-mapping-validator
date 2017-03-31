<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class SqlResultSetColumnResultMapping
{
    /** @var SqlResultSetMapping */
    protected $sqlResultSetMapping;

    /** @var ?string */
    protected $name;

    public function __construct(SqlResultSetMapping $sqlResultSetMapping)
    {
        $this->sqlResultSetMapping = $sqlResultSetMapping;
    }

    public function getSqlResultSetMapping(): SqlResultSetMapping
    {
        return $this->sqlResultSetMapping;
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
}
