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
            $this->mandatory = false;
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
    }

    /**
     * @return string|null
     */
    public function getType() : ?string
    {
        return $this->type ?: null;
    }

    /**
     * @return bool
     */
    public function isMandatory() : bool
    {
        return $this->mandatory ?: false;
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
}
