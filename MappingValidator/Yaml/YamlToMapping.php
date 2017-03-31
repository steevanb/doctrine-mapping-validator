<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator\Yaml;

use steevanb\DoctrineMappingValidator\MappingValidator\{
    Exception\MappingValueTypeException,
    Mapping,
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
            ->validateRootKnownKeys()
            ->validateCacheKnownKeys()
            ->validateNamedQueriesKnownKeys()
            ->validateNamedNativeQueriesKnownKeys();

        $this
            ->defineRootMapping()
            ->defineCacheMapping();

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
            $error = 'Unknow mapping configuration' . (count($diff) > 1 ? 's' : null) . ': ';
            $error .= implode(', ', array_map(
                function($value) use ($prefix) {
                    return $prefix . $value;
                },
                $diff
            ));
            $error .= '. Allowed configuration' . (count($diff) > 1 ? 's' : null) . ': ';
            $error .= implode(', ', $keys) . '.';
            $this->errors[] = $error;
        }

        return $this;
    }

    protected function validateRootKnownKeys(): self
    {
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

    protected function validateCacheKnownKeys(): self
    {
        return $this->validateKnownKeys($this->data['cache'] ?? [], ['region', 'usage'], 'cache.');
    }

    protected function validateNamedQueriesKnownKeys(): self
    {
        foreach ($this->data['namedQueries'] ?? [] as $name => $queryMapping) {
            if (is_array($queryMapping)) {
                $this->validateKnownKeys($queryMapping, ['name', 'query'], 'namedQueries.' . $name . '.');
            }
        }

        return $this;
    }

    protected function validateNamedNativeQueriesKnownKeys(): self
    {
        foreach ($this->data['namedNativeQueries'] ?? [] as $name => $queryMapping) {
            $this->validateKnownKeys(
                $queryMapping,
                ['name', 'query', 'resultClass', 'resultSetMapping'],
                'namedNativeQueries.' . $name . '.'
            );
        }

        return $this;
    }

    protected function defineRootMapping(): self
    {
        $this
            ->mapping
            ->setType($this->getYamlValue('type', $this->data['type'] ?? null))
            ->setRepositoryClass($this->getYamlValue('repositoryClass', $this->data['repositoryClass'] ?? null))
            ->setReadOnly($this->getYamlValue('readOnly', $this->data['readOnly'] ?? null, ['bool', 'null']))
            ->setTable($this->getYamlValue('table', $this->data['table'] ?? null))
            ->setSchema($this->getYamlValue('schema', $this->data['schema'] ?? null));

        return $this;
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

    protected function defineNamedQueriesMapping(): self
    {
        foreach ($this->data['namedQueries'] ?? [] as $name => $queryMappingData) {
            $queryMapping = new NamedQueryMapping($this->mapping);
            if (is_string($queryMappingData)) {
                $queryMapping
                    ->setName($name)
                    ->setQuery($queryMappingData);
            } else {
                $queryMapping
                    ->setName($queryMappingData['name'] ?? $name)
                    ->setQuery($queryMappingData['query'] ?? null);
            }
            $this->mapping->addNamedQuery($queryMapping);
        }

        return $this;
    }

    /** @return string|int|float|bool */
    protected function getYamlValue(string $name, $value, $allowedTypes = ['string', 'null'])
    {
        if (is_string($allowedTypes)) {
            $allowedTypes = [$allowedTypes];
        }
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

        return $value;
    }
}
