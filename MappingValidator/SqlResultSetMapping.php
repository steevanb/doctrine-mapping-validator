<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class SqlResultSetMapping
{
    /** @var Mapping */
    protected $mapping;

    /** @var ?string */
    protected $name;

    /** @var SqlResultSetEntityResultMapping[] */
    protected $entityResults = [];

    /** @var SqlResultSetColumnResultMapping[] */
    protected $columnResults = [];

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

    public function addEntityResult(SqlResultSetEntityResultMapping $entityResult): self
    {
        $this->entityResults[] = $entityResult;

        return $this;
    }

    /** @return SqlResultSetEntityResultMapping[] */
    public function getEntityResults(): array
    {
        return $this->entityResults;
    }

    public function addColumnResult(SqlResultSetColumnResultMapping $columnResult): self
    {
        $this->columnResults[] = $columnResult;

        return $this;
    }

    /** @return SqlResultSetColumnResultMapping[] */
    public function getColumnResults(): self
    {
        return $this->columnResults;
    }
}
