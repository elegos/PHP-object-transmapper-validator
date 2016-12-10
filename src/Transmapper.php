<?php

namespace GiacomoFurlan\ObjectTransmapperValidator;

use Doctrine\Common\Annotations\Reader;
use Exception;
use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Exception\TransmappingException;
use ReflectionObject;
use stdClass;

/**
 * Class Transmapper
 * @package GiacomoFurlan\ObjectTransmapperValidator
 */
class Transmapper
{
    /** @var Reader */
    private $reader;

    /**
     * Transmapper constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param stdClass $object
     * @param string   $className
     * @param string   $attributePrefix used for recursive calls
     *
     * @return mixed (object of class $className)
     * @throws TransmappingException
     * @throws Exception
     */
    public function map(stdClass $object, string $className, string $attributePrefix = '')
    {
        if (strpos($attributePrefix, '.') === 0) {
            $attributePrefix = substr($attributePrefix, 1);
        }

        $mappedObject = new $className();

        $reflectionObject = new ReflectionObject($mappedObject);

        $classProperties = $reflectionObject->getProperties();

        foreach ($classProperties as $property) {
            /** @var Validate|null $annotation */
            $annotation = $this->reader->getPropertyAnnotation($property, Validate::class);
            $propertyName = $property->getName();

            // Mandatory check
            $this->checkMandatoryConstraint($annotation, $object, $propertyName, $attributePrefix);

            if (!array_key_exists($propertyName, get_object_vars($object))) {
                continue;
            }

            $value = $object->$propertyName;
            $property->setAccessible(true);

            if (null === $annotation) {
                // Blind mapping
                $property->setValue($mappedObject, $value);

                continue;
            }

            // Type check
            $this->checkTypeConstraint($annotation, $value);

            // Regex check
            $this->checkRegexConstraint($annotation, $value);

            $expectedType = $annotation->getType();
            $this->mapAttribute(
                $reflectionObject,
                $annotation,
                $propertyName,
                $expectedType,
                $attributePrefix,
                $mappedObject,
                $value
            );
        }

        return $mappedObject;
    }

    /**
     * @param Validate|null $annotation
     * @param stdClass      $object
     * @param string        $propertyName
     * @param string        $attributePrefix
     *
     * @throws Exception
     */
    private function checkMandatoryConstraint(
        Validate $annotation = null,
        stdClass $object,
        string $propertyName,
        string $attributePrefix
    ) {
        if (
            null !== $annotation
            && !array_key_exists($propertyName, get_object_vars($object))
            && $annotation->isMandatory()
        ) {
            $mandatoryExceptionClass = $annotation->getMandatoryExceptionClass();

            $missingField = '' !== $attributePrefix ? $attributePrefix.'.'.$propertyName : $propertyName;

            throw new $mandatoryExceptionClass(
                sprintf($annotation->getMandatoryExceptionMessage(), $missingField),
                $annotation->getMandatoryExceptionCode()
            );
        }
    }

    /**
     * @param Validate    $annotation
     * @param mixed       $value
     * @param string|null $forcedExpectedType
     * @param bool|null   $forceNullable
     */
    private function checkTypeConstraint(Validate $annotation, $value, $forcedExpectedType = null, $forceNullable = null)
    {
        $expectedType = null !== $forcedExpectedType ? $forcedExpectedType : $annotation->getType();
        $nullable = null !== $forceNullable ? $forceNullable : $annotation->isNullable();
        $typeExceptionClass = $annotation->getTypeExceptionClass();

        $foundType = is_object($value) ? get_class($value) : gettype($value);

        $exception = new $typeExceptionClass(
            sprintf($annotation->getTypeExceptionMessage(), $foundType, $expectedType),
            $annotation->getTypeExceptionCode()
        );

        if (null === $value) {
            if (!$nullable) {
                throw $exception;
            } else {
                return;
            }
        }

        $isArray = $this->isArray($expectedType);

        if (!$isArray) {
            // Single value

            if (
                ('string' === $expectedType && !is_string($value))
                || (in_array($expectedType, ['bool', 'boolean'], true) && !is_bool($value))
                || (in_array($expectedType, ['float', 'double'], true) && !is_float($value))
                || (in_array($expectedType, ['int', 'integer'], true) && !is_int($value))
            ) {
                // Not a scalar value as expected

                throw $exception;
            } elseif (!is_object($value) && class_exists($expectedType)) {
                // Not an object as expected

                throw $exception;
            }
        } elseif (!is_array($value)) {
            // Array

            throw $exception;
        }
    }

    /**
     * @param Validate $annotation
     * @param          $value
     *
     * @throws Exception
     */
    private function checkRegexConstraint(Validate $annotation, $value)
    {
        $regex = $annotation->getRegex();

        // Not a string, or regex not set
        if (!is_string($value) || null === $regex) {
            return;
        }

        if (!preg_match($regex, $value)) {
            $exceptionClass = $annotation->getRegexExceptionClass();

            throw new $exceptionClass(
                sprintf($annotation->getRegexExceptionMessage(), $value, $regex),
                $annotation->getRegexExceptionCode()
            );
        }
    }

    /**
     * @param ReflectionObject $reflectionObject
     * @param Validate         $annotation
     * @param string           $propertyName
     * @param string           $expectedType
     * @param string           $attributePrefix
     * @param mixed            $mappedObject
     * @param mixed            $value
     *
     * @throws Exception
     */
    private function mapAttribute(
        ReflectionObject $reflectionObject,
        Validate $annotation,
        string $propertyName,
        string $expectedType,
        string $attributePrefix,
        $mappedObject,
        $value
    ) {
        $property = $reflectionObject->getProperty($propertyName);
        $property->setAccessible(true);

        if ($this->isArray($expectedType)) {
            if ($this->isScalarArray($expectedType)) {
                // Scalar array
                $property->setValue(
                    $mappedObject,
                    $this->mapScalarArrayAttribute($annotation, $expectedType, $value)
                );

                return;
            } else {
                // Object array
                $property->setValue(
                    $mappedObject,
                    $this->mapObjectArrayAttribute($annotation, $expectedType, $value, $attributePrefix)
                );
            }
        } else {
            // Simple scalar value
            if ($this->isScalar($expectedType)) {
                $property->setValue($mappedObject, $value);
            } else {
                // Object
                $property->setValue($mappedObject, $this->map($value, $expectedType, $attributePrefix . '.' . $propertyName));
            }
        }
    }

    /**
     * @param Validate $annotation
     * @param string   $expectedType
     * @param array    $value
     *
     * @return array
     * @throws Exception
     */
    private function mapScalarArrayAttribute(
        Validate $annotation,
        string $expectedType,
        array $value
    ) : array
    {
        $expectedType = preg_replace('/\[\]$/', '', $expectedType);

        $values = [];

        foreach ($value as $item) {
            $this->checkTypeConstraint($annotation, $item, $expectedType, false);

            $values[] = $item;
        }

        return $values;
    }

    /**
     * @param Validate $annotation
     * @param string   $expectedType
     * @param array    $value
     * @param string   $attributePrefix
     *
     * @return array
     * @throws Exception
     */
    private function mapObjectArrayAttribute(
        Validate $annotation,
        string $expectedType,
        array $value,
        string $attributePrefix
    ) : array
    {
        $expectedType = preg_replace('/\[\]$/', '', $expectedType);

        $values = [];

        foreach ($value as $item) {
            $this->checkTypeConstraint($annotation, $item, $expectedType, false);

            $values[] = $this->map($item, $expectedType, $attributePrefix.'.'.$expectedType.'[]');
        }

        return $values;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isScalar(string $type) : bool
    {
        return in_array(strtolower($type), ['bool', 'boolean', 'double', 'float', 'int', 'integer', 'string'], true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isArray(string $type) : bool
    {
        return false !== strpos($type, '[]') && preg_match('/\[\]$/', $type);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isScalarArray(string $type) : bool
    {
        return in_array(strtolower($type), ['bool[]', 'boolean[]', 'double[]', 'float[]', 'int[]', 'integer[]', 'string[]'], true);
    }
}
