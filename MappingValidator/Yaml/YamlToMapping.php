<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator\Yaml;

use steevanb\DoctrineMappingValidator\MappingValidator\{
    Exception\MappingValueTypeException,
    InheritanceTypeDiscriminatorMapMapping,
    Mapping,
    NamedNativeQueryMapping,
    NamedQueryMapping
};

class YamlToMapping
{
    /** @var string */
    protected $file;

    /** @var string */
    protected $className;

    /** @var array */
    protected $data = [];

    /** @var Mapping */
    protected $mapping;

    /** @var string[] */
    protected $errors = [];

    public function __construct(string $file, string $className, array $data)
    {
        $this->file = $file;
        $this->className = $className;
        $this->data = $data;
    }

    public function createMapping()
    {
        $this->mapping = new Mapping($this->file, $this->className);

        $this
            ->validateRootData()
            ->validateCacheData()
            ->validateNamedQueriesData()
            ->validateNamedNativeQueriesData()
            ->validateDiscriminatorColumnData()
            ->validateDiscriminatorMapData();

        $this
            ->defineRootMapping()
            ->defineCacheMapping()
            ->defineNamedQueriesMapping()
            ->defineNamedNativeQueriesMapping()
            ->defineDiscriminatorColumn()
            ->defineDiscriminatorMap()
            ->defineChangeTrackingPolicy();

        return $this->mapping;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function validateKnownKeys(array $data, array $keys, string $prefix = null): self
    {
        $diff = array_values(array_diff(array_keys($data), $keys));
        if (count($diff) > 0) {
            $error = 'Unknow mapping key' . (count($diff) > 1 ? 's' : null) . ': ';
            $error .= implode(', ', array_map(
                function($value) use ($prefix) {
                    return $prefix . $value;
                },
                $diff
            ));
            $error .= '. Allowed key' . (count($keys) > 1 ? 's' : null) . ': ';
            $error .= implode(', ', $keys) . '.';
            $this->errors[] = $error;
        }

        return $this;
    }

    protected function validateRootData(): self
    {
        $this->assertValueType('root', $this->data, ['array', 'null']);

        return $this->validateKnownKeys($this->data, [
            'type',
            'repositoryClass',
            'readOnly',
            'table',
            'schema',
            'cache',
            'namedQueries',
            'namedNativeQueries',
            'sqlResultSetMappings',
            'inheritanceType',
            'discriminatorColumn',
            'discriminatorMap',
            'changeTrackingPolicy',
            'indexes',
            'uniqueConstraints',
            'options',
            'id',
            'fields',
            'embedded',
            'oneToOne',
            'oneToMany',
            'manyToOne',
            'manyToMany',
            'associationOverride',
            'attributeOverride',
            'lifecycleCallbacks',
            'entityListeners'
        ]);
    }

    protected function defineRootMapping(): self
    {
        $this
            ->mapping
            ->setType($this->getYamlValue('type', $this->data['type'] ?? null))
            ->setRepositoryClass($this->getYamlValue('repositoryClass', $this->data['repositoryClass'] ?? null))
            ->setReadOnly($this->getYamlValue('readOnly', $this->data['readOnly'] ?? null, ['bool', 'null']))
            ->setTable($this->getYamlValue('table', $this->data['table'] ?? null))
            ->setSchema($this->getYamlValue('schema', $this->data['schema'] ?? null))
            ->setInheritanceType($this->getYamlValue('inheritanceType', $this->data['inheritanceType'] ?? null));

        return $this;
    }

    protected function validateCacheData(): self
    {
        $this->assertValueType('cache', $this->data['cache'] ?? [], ['array', 'null']);

        return $this->validateKnownKeys($this->data['cache'] ?? [], ['region', 'usage'], 'cache.');
    }

    protected function defineCacheMapping(): self
    {
        $this
            ->mapping
            ->getCache()
            ->setRegion($this->getYamlValue('cache.region', $this->data['cache']['region'] ?? null))
            ->setUsage($this->getYamlValue('cache.usage', $this->data['cache']['usage'] ?? null));

        return $this;
    }

    protected function validateNamedQueriesData(): self
    {
        $this->assertValueType('namedQueries', $this->data['namedQueries'] ?? [], ['array', 'null']);

        foreach ($this->data['namedQueries'] ?? [] as $name => $queryMapping) {
            $this->assertValueType('namedQueries.' . $name, $queryMapping ?? [], ['string', 'array']);
            if (is_array($queryMapping)) {
                $this->validateKnownKeys($queryMapping, ['name', 'query'], 'namedQueries.' . $name . '.');
            }
        }

        return $this;
    }

    protected function defineNamedQueriesMapping(): self
    {
        foreach ($this->data['namedQueries'] ?? [] as $name => $queryMappingData) {
            $queryMapping = new NamedQueryMapping();
            if (is_string($queryMappingData)) {
                $queryMapping
                    ->setName($name)
                    ->setQuery($queryMappingData);
            } else {
                $queryMapping
                    ->setName(
                        $this->getYamlValue('namedQueries.' . $name . '.name', $queryMappingData['name'] ?? $name)
                    )
                    ->setQuery(
                        $this->getYamlValue('namedQueries.' . $name . '.query', $queryMappingData['query'] ?? null)
                    );
            }
            $this->mapping->addNamedQuery($queryMapping);
        }

        return $this;
    }

    protected function validateNamedNativeQueriesData(): self
    {
        $this->assertValueType('namedNativeQueries', $this->data['namedNativeQueries'] ?? [], ['array', 'null']);

        foreach ($this->data['namedNativeQueries'] ?? [] as $name => $queryMapping) {
            $this->validateKnownKeys(
                $queryMapping,
                ['name', 'query', 'resultClass', 'resultSetMapping'],
                'namedNativeQueries.' . $name . '.'
            );
        }

        return $this;
    }

    protected function defineNamedNativeQueriesMapping(): self
    {
        $this->assertValueType('namedNativeQueries', $this->data['namedNativeQueries'] ?? [], ['array', 'null']);

        foreach ($this->data['namedNativeQueries'] ?? [] as $name => $queryMappingData) {
            $queryMapping = new NamedNativeQueryMapping();
            $queryMapping
                ->setName(
                    $this->getYamlValue('namedNativeQueries.' . $name . '.name', $queryMappingData['name'] ?? $name)
                )
                ->setQuery(
                    $this->getYamlValue('namedNativeQueries.' . $name . '.query', $queryMappingData['query'] ?? null)
                )
                ->setResultClass(
                    $this->getYamlValue(
                        'namedNativeQueries.' . $name . '.resultClass',
                        $queryMappingData['resultClass'] ?? null
                    )
                )
                ->setResultSetMapping(
                    $this->getYamlValue(
                        'namedNativeQueries.' . $name . '.resultSetMapping',
                        $queryMappingData['resultSetMapping'] ?? null
                    )
                );
            $this->mapping->addNamedNativeQuery($queryMapping);
        }

        return $this;
    }

    protected function validateDiscriminatorColumnData(): self
    {
        $this->assertValueType('discriminatorColumn', $this->data['discriminatorColumn'] ?? [], ['array', 'null']);

        return $this->validateKnownKeys(
            $this->data['discriminatorColumn'] ?? [],
            ['name', 'length', 'type', 'columnDefinition'],
            'discriminatorColumn.'
        );
    }

    protected function defineDiscriminatorColumn(): self
    {
        $this
            ->mapping
            ->getDiscriminatorColumn()
            ->setName(
                $this->getYamlValue('discriminatorColumn.name', $this->data['discriminatorColumn']['name'] ?? null)
            )
            ->setLength(
                $this->getYamlValue(
                    'discriminatorColumn.length',
                    $this->data['discriminatorColumn']['length'] ?? null,
                    ['int', 'null']
                )
            )
            ->setType(
                $this->getYamlValue('discriminatorColumn.type', $this->data['discriminatorColumn']['type'] ?? null)
            )
            ->setColumnDefinition(
                $this->getYamlValue(
                    'discriminatorColumn.columnDefinition',
                    $this->data['discriminatorColumn']['columnDefinition'] ?? null
                )
            );

        return $this;
    }

    protected function validateDiscriminatorMapData(): self
    {
        $this->assertValueType('discriminatorMap', $this->data['discriminatorMap'] ?? [], ['array', 'null']);
        foreach ($this->data['discriminatorMap'] ?? [] as $value => $className) {
            $this->assertValueType('discriminatorMap.' . $value, $className, ['string']);
        }

        return $this;
    }

    protected function defineDiscriminatorMap(): self
    {
        foreach ($this->data['discriminatorMap'] ?? [] as $value => $className) {
            $map = new InheritanceTypeDiscriminatorMapMapping();
            $map
                ->setName($this->getYamlValue('discriminatorMap.' . $value, $value, ['string', 'int', 'float']))
                ->setClassName($this->getYamlValue('discriminatorMap.' . $value, $className, ['string', null]));
            $this->mapping->addDiscriminatorMap($map);
        }

        return $this;
    }

    protected function defineChangeTrackingPolicy(): self
    {
        $this->mapping->setChangeTrackingPolicy(
            $this->getYamlValue('changeTrackingPolicy', $this->data['changeTrackingPolicy'] ?? null)
        );

        return $this;
    }

    /** @return string|int|float|bool */
    protected function getYamlValue(string $name, $value, array $allowedTypes = ['string', 'null'])
    {
        $this->assertValueType($name, $value, $allowedTypes);

        return $value;
    }

    protected function assertValueType(string $name, $value, array $allowedTypes = ['string', 'null']): self
    {
        $isAllowedType = false;
        foreach ($allowedTypes as $allowedType) {
            if (call_user_func('is_' . $allowedType, $value)) {
                $isAllowedType = true;
                break;
            }
        }
        if ($isAllowedType === false) {
            throw new MappingValueTypeException($this->mapping, $name, $value, $allowedTypes);
        }

        return $this;
    }
}
