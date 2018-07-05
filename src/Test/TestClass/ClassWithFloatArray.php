<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithFloatArray
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithFloatArray
{
    /**
     * @var float[]
     * @Validate(type="float[]", mandatory=true)
     */
    private $floatArray;

    /**
     * @return float[]
     */
    public function getFloatArray(): array
    {
        return $this->floatArray;
    }
}
