<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Mapping\Cache;

class CacheMapping
{
    /** @var ?string */
    protected $region;

    /** @var ?string */
    protected $usage;

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
