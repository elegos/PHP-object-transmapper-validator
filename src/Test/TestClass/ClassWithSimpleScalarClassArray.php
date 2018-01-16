<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithSimpleScalarClassArray
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithSimpleScalarClassArray
{
    /**
     * @var SimpleScalarClass[]
     * @Validate(type="GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\SimpleScalarClass[]", mandatory=true)
     */
    private $innerScalarArray;

    /**
     * @return SimpleScalarClass[]
     */
    public function getInnerScalarArray(): array
    {
        return $this->innerScalarArray;
    }
}
