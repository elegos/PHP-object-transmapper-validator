<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithIntArray
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithIntArray
{
    /**
     * @var int[]
     * @Validate(type="int[]", mandatory=true)
     */
    private $intArray;

    /**
     * @return int[]
     */
    public function getIntArray(): array
    {
        return $this->intArray;
    }
}
