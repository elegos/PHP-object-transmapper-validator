<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithInnerMappedModel
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithInnerMappedModel
{
    /**
     * @var ClassWithMappedModel
     * @Validate(type="GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithMappedModel")
     */
    private $inner;

    /**
     * @return ClassWithMappedModel
     */
    public function getInner(): ClassWithMappedModel
    {
        return $this->inner;
    }
}
