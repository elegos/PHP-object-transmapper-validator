<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithSimpleScalarClassInside
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithSimpleScalarClassInside
{
    /**
     * @var SimpleScalarClass
     * @Validate(
     *     type="GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\SimpleScalarClass",
     *     mandatory=true
     * )
     */
    private $innerClass;

    /**
     * @return SimpleScalarClass
     */
    public function getInnerClass() : SimpleScalarClass
    {
        return $this->innerClass;
    }
}
