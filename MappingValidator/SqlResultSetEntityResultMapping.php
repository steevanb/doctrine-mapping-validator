<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class SqlResultSetEntityResultMapping
{
    /** @var ?string */
    protected $entityClass;

    /** @var ?string */
    protected $discriminatorColumn;

    /** @var SqlResultSetEntityFieldResultMapping[] */
    protected $fieldResults = [];

    public function setEntityClass(?string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setDiscriminatorColumn(?string $discriminatorColumn): self
    {
        $this->discriminatorColumn = $discriminatorColumn;

        return $this;
    }

    public function getDiscriminatorColumn(): ?string
    {
        return $this->discriminatorColumn;
    }

    public function addFieldResult(SqlResultSetEntityFieldResultMapping $fieldResult): self
    {
        $this->fieldResults[] = $fieldResult;

        return $this;
    }

    /** @return SqlResultSetEntityFieldResultMapping[] */
    public function getFieldResults(): array
    {
        return $this->fieldResults;
    }
}
