<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;

/**
 * Class SimpleScalarClass
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass
 */
class SimpleScalarClass
{
    /**
     * @var bool
     * @Validate(
     *     type="boolean",
     *     mandatory=true,
     *     typeExceptionClass="GiacomoFurlan\ObjectTransmapperValidator\Test\TestException\WrongTypeAttributeException",
     *     mandatoryExceptionClass="GiacomoFurlan\ObjectTransmapperValidator\Test\TestException\MissingMandatoryAttributeException"
     * )
     */
    private $boolean;

    /**
     * @var bool|null
     * @Validate(type="bool", mandatory=false)
     */
    private $bool;

    /**
     * @var int
     * @Validate(type="integer", mandatory=true)
     */
    private $integer;

    /**
     * @var int|null
     * @Validate(type="int", mandatory=false)
     */
    private $int;

    /**
     * @var float
     * @Validate(type="float", mandatory=true)
     */
    private $float;

    /**
     * @var double|null
     * @Validate(type="double", mandatory=false)
     */
    private $double;

    /**
     * @var string
     * @Validate(type="string", mandatory=true)
     */
    private $string;

    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->boolean;
    }

    /**
     * @return bool|null
     */
    public function isBool(): ?bool
    {
        return $this->bool;
    }

    /**
     * @return int
     */
    public function getInteger(): int
    {
        return $this->integer;
    }

    /**
     * @return int|null
     */
    public function getInt(): ?int
    {
        return $this->int;
    }

    /**
     * @return float
     */
    public function getFloat(): float
    {
        return $this->float;
    }

    /**
     * @return float|null
     */
    public function getDouble(): ?float
    {
        return $this->double;
    }

    /**
     * @return string
     */
    public function getString(): string
    {
        return $this->string;
    }
}
