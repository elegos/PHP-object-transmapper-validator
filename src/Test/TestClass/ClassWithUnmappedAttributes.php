<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithUnmappedAttributes
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithUnmappedAttributes
{
    /**
     * @var bool
     * @Validate(type="bool", mandatory=true)
     */
    private $boolean;

    private $one;
    private $two;
    private $three;

    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->boolean;
    }

    /**
     * @return mixed
     */
    public function getOne()
    {
        return $this->one;
    }

    /**
     * @return mixed
     */
    public function getTwo()
    {
        return $this->two;
    }

    /**
     * @return mixed
     */
    public function getThree()
    {
        return $this->three;
    }
}
