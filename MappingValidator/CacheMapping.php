<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class CacheMapping
{
    /** @var Mapping */
    protected $mapping;

    /** @var ?string */
    protected $region;

    /** @var ?string */
    protected $usage;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setUsage(?string $usage): self
    {
        $this->usage = $usage;

        return $this;
    }

    public function getUsage(): ?string
    {
        return $this->usage;
    }
}
