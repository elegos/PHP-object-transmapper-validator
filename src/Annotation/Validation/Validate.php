<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation;

use GiacomoFurlan\ObjectTransmapperValidator\Exception\ValidationException;
use InvalidArgumentException;

/**
 * Class Validate
 * @package GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Validate
{
    /** @var string */
    private $type;

    /** @var bool */
    private $mandatory;

    /** @var bool */
    private $nullable;

    /** @var string|null */
    private $regex;

    /** @var string */
    private $typeExceptionClass;

    /**
     * @var string requires two %s: (1) found type, (2) expected type
     */
    private $typeExceptionMessage;

    /** @var int */
    private $typeExceptionCode;

    /** @var string */
    private $mandatoryExceptionClass;

    /**
     * @var string requires one %s: attribute's name
     */
    private $mandatoryExceptionMessage;

    /** @var int */
    private $mandatoryExceptionCode;

    /** @var string */
    private $regexExceptionClass;

    /**
     * @var string requires one %s: attribute's name
     */
    private $regexExceptionMessage;

    /** @var int */
    private $regexExceptionCode;

    /**
     * Validate constructor.
     *
     * @param array $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
            }

            $this->$key = $value;
        }

        if (null === $this->type) {
            throw new InvalidArgumentException('type is mandatory');
        }

        if (null === $this->mandatory) {
            $this->mandatory = true;
        }

        if (null === $this->nullable) {
            $this->nullable = false;
        }

        if (null === $this->typeExceptionClass) {
            $this->typeExceptionClass = ValidationException::class;
        }

        if (null === $this->typeExceptionMessage) {
            $this->typeExceptionMessage = 'Invalid type "%s" (expected "%s")';
        }

        if (null === $this->typeExceptionCode) {
            $this->typeExceptionCode = 3000;
        }

        if (null === $this->mandatoryExceptionClass) {
            $this->mandatoryExceptionClass = ValidationException::class;
        }

        if (null === $this->mandatoryExceptionMessage) {
            $this->mandatoryExceptionMessage = 'Attribute %s is mandatory';
        }

        if (null === $this->mandatoryExceptionCode) {
            $this->mandatoryExceptionCode = 3001;
        }

        if (null === $this->regexExceptionClass) {
            $this->regexExceptionClass = ValidationException::class;
        }

        if (null === $this->regexExceptionMessage) {
            $this->regexExceptionMessage = 'Regex constraint fail ("%s" doesn\'t match "%s")';
        }

        if (null === $this->regexExceptionCode) {
            $this->regexExceptionCode = 3002;
        }
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isMandatory() : bool
    {
        return $this->mandatory;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @return string|null
     */
    public function getRegex(): ?string
    {
        return $this->regex;
    }

    /**
     * @return string
     */
    public function getTypeExceptionClass(): string
    {
        return $this->typeExceptionClass;
    }

    /**
     * @return string
     */
    public function getTypeExceptionMessage(): string
    {
        return $this->typeExceptionMessage;
    }

    /**
     * @return string
     */
    public function getTypeExceptionCode(): string
    {
        return $this->typeExceptionCode;
    }

    /**
     * @return string
     */
    public function getMandatoryExceptionClass(): string
    {
        return $this->mandatoryExceptionClass;
    }

    /**
     * @return string
     */
    public function getMandatoryExceptionMessage(): string
    {
        return $this->mandatoryExceptionMessage;
    }

    /**
     * @return string
     */
    public function getMandatoryExceptionCode(): string
    {
        return $this->mandatoryExceptionCode;
    }

    /**
     * @return string
     */
    public function getRegexExceptionClass(): string
    {
        return $this->regexExceptionClass;
    }

    /**
     * @return string
     */
    public function getRegexExceptionMessage(): string
    {
        return $this->regexExceptionMessage;
    }

    /**
     * @return int
     */
    public function getRegexExceptionCode(): int
    {
        return $this->regexExceptionCode;
    }

    /**
     * Overridable attributes: mandatory, nullable, regex
     *
     * @param $attribute
     * @param $value
     */
    public function __set($attribute, $value)
    {
        $overridable = ['mandatory', 'nullable', 'regex'];

        if (in_array($attribute, $overridable, true)) {
            $this->$attribute = $value;
        }
    }
}
