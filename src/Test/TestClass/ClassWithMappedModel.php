<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Model\MappedModel;

/**
 * Class ClassWithMappedModel
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithMappedModel extends MappedModel
{
    /**
     * @var string
     * @Validate(type="string", mandatory=true)
     */
    private $mapped;

    /**
     * @var string|null
     * @Validate(type="string", mandatory=false)
     */
    private $notMapped;

    /**
     * @return string
     */
    public function getMapped(): string
    {
        return $this->mapped;
    }

    /**
     * @return string
     */
    public function getNotMapped(): ?string
    {
        return $this->notMapped;
    }
}
