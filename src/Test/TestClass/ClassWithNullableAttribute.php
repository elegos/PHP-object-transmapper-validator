<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithNullableAttribute
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithNullableAttribute
{
    /**
     * @var int|null
     * @Validate(
     *     type="int",
     *     nullable=true
     * )
     */
    private $nullableInt;

    /**
     * @return int|null
     */
    public function getNullableInt()
    {
        return $this->nullableInt;
    }
}
