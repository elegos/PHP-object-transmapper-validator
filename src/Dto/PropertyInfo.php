<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Dto;

/**
 * Class AttributeInfo
 * @package GiacomoFurlan\ObjectTransmapperValidator\Dto
 */
class PropertyInfo
{
    /** @var string */
    private $fqcn;

    /** @var string */
    private $attributeName;

    /**
     * AttributeInfo constructor.
     *
     * @param string $fqcn
     * @param string $attributeName
     */
    public function __construct(string $fqcn, string $attributeName)
    {
        $this->fqcn = $fqcn;
        $this->attributeName = $attributeName;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s::%s',
            $this->fqcn,
            $this->attributeName
        );
    }
}
