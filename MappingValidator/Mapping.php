<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class Mapping
{
    /** @var string */
    protected $source;

    /** @var string */
    protected $className;

    /** @var ?string */
    protected $type;

    /** @var ?string */
    protected $repositoryClass;

    /** @var ?bool */
    protected $readOnly;

    /** @var ?string */
    protected $table;

    /** @var ?string */
    protected $schema;

    /** @var CacheMapping */
    protected $cache;

    /** @var NamedQueryMapping[] */
    protected $namedQueries = [];

    /** @var NamedNativeQueryMapping[] */
    protected $namedNativeQueries = [];

    /** @var SqlResultSetMapping[] */
    protected $sqlResultSetMappings = [];

    /** @var ?string */
    protected $inheritanceType;

    /** @var InheritanceTypeDiscriminatorColumnMapping */
    protected $discriminatorColumn;

    /** @var InheritanceTypeDiscriminatorMapMapping[] */
    protected $discriminatorMaps = [];

    /** @var ?string */
    protected $changeTrackingPolicy;

    /** @var IndexMapping[] */
    protected $indexes = [];

    /** @var OptionMapping[] */
    protected $options = [];

    /** @var FieldMapping[] */
    protected $fields = [];

    public function __construct(string $source, string $className)
    {
        $this->source = $source;
        $this->className = $className;
        $this->cache = new CacheMapping($this);
        $this->discriminatorColumn = new InheritanceTypeDiscriminatorColumnMapping($this);
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getClassName(): string
    {
        return $this->className;
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

    public function setRepositoryClass(?string $repositoryClass): self
    {
        $this->repositoryClass = $repositoryClass;

        return $this;
    }

    public function getRepositoryClass(): ?string
    {
        return $this->repositoryClass;
    }

    public function setReadOnly(?bool $readOnly): self
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function getReadOnly(): ?bool
    {
        return $this->readOnly;
    }

    public function setTable(?string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function setSchema(?string $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getCache(): CacheMapping
    {
        return $this->cache;
    }

    public function addNamedQuery(NamedQueryMapping $namedQuery): self
    {
        $this->namedQueries[] = $namedQuery;

        return $this;
    }

    /** @return NamedQueryMapping[] */
    public function getNamedQueries(): array
    {
        return $this->namedQueries;
    }

    public function addNamedNativeQuery(NamedNativeQueryMapping $namedNativeQuery): self
    {
        $this->namedNativeQueries[] = $namedNativeQuery;

        return $this;
    }

    /** @return NamedNativeQueryMapping[] */
    public function getNamedNativeQueries(): array
    {
        return $this->namedNativeQueries;
    }

    public function setInheritanceType(?string $inheritanceType): self
    {
        $this->inheritanceType = $inheritanceType;

        return $this;
    }

    public function getInheritanceType(): ?string
    {
        return $this->inheritanceType;
    }

    public function getDiscriminatorColumn(): InheritanceTypeDiscriminatorColumnMapping
    {
        return $this->discriminatorColumn;
    }

    public function addDiscriminatorMap(InheritanceTypeDiscriminatorMapMapping $discriminatorMap): self
    {
        $this->discriminatorMaps[] = $discriminatorMap;

        return $this;
    }

    /** @return InheritanceTypeDiscriminatorMapMapping[] */
    public function getDiscriminatorMaps(): array
    {
        return $this->discriminatorMaps;
    }

    public function setChangeTrackingPolicy(?string $changeTrackingPolicy): self
    {
        $this->changeTrackingPolicy = $changeTrackingPolicy;

        return $this;
    }

    public function getChangeTrackingPolicy(): ?string
    {
        return $this->changeTrackingPolicy;
    }

    public function addSqlResultSetMapping(SqlResultSetMapping $sqlResultSet): self
    {
        $this->sqlResultSetMappings[] = $sqlResultSet;

        return $this;
    }

    /** @return SqlResultSetMapping[] */
    public function getSqlResultSetMappings(): array
    {
        return $this->sqlResultSetMappings;
    }

    public function addIndex(IndexMapping $index): self
    {
        $this->indexes[] = $index;

        return $this;
    }

    /** @return IndexMapping[] */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function addOption(OptionMapping $option): self
    {
        $this->options[] = $option;

        return $this;
    }

    /** @return OptionMapping[] */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function addField(FieldMapping $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    /** @return FieldMapping[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
