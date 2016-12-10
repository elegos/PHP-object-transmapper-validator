<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class ClassWithRegex
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class ClassWithRegex
{
    /**
     * @var string
     * @Validate(type="string", regex="/^[a-zA-Z]{5}$/")
     */
    private $string;

    /**
     * @var int
     * @Validate(type="int", regex="/[a-zA-Z]{5}/")
     */
    private $int;

    /**
     * @return string
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * @return int
     */
    public function getInt(): int
    {
        return $this->int;
    }
}
