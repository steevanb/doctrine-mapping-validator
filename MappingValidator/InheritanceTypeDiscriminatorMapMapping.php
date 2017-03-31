<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\MappingValidator;

class InheritanceTypeDiscriminatorMapMapping
{
    /** @var Mapping */
    protected $mapping;

    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $className;

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

    public function setClassName(?string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
}
