<?php

namespace GiacomoFurlan\ObjectTransmapperValidator;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Reader;
use Exception;
use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Exception\TransmappingException;
use ReflectionObject;
use ReflectionProperty;
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
            if (!$this->checkMandatoryConstraint($annotation, $object, $propertyName)) {
                $mandatoryExceptionClass = $annotation->getMandatoryExceptionClass();

                $missingField = '' !== $attributePrefix ? $attributePrefix.'.'.$propertyName : $propertyName;

                throw new $mandatoryExceptionClass(
                    sprintf($annotation->getMandatoryExceptionMessage(), $missingField),
                    $annotation->getMandatoryExceptionCode()
                );
            }

            if (!array_key_exists($propertyName, get_object_vars($object))) {
                continue;
            }

            $value = $object->$propertyName;
            $property->setAccessible(true);

            // Check the type
            if (null !== $annotation) {
                $expectedType = $annotation->getType();
                $typeExceptionClass = $annotation->getTypeExceptionClass();
                $typeExceptionMessage = $annotation->getTypeExceptionMessage();
                $typeExceptionCode = $annotation->getTypeExceptionCode();

                $isNullable = $annotation->isNullable();

                if (!$this->checkType($expectedType, $isNullable, $value)) {
                    $foundType = is_object($value) ? get_class($value) : gettype($value);

                    throw new $typeExceptionClass(
                        sprintf($typeExceptionMessage, $foundType, $expectedType),
                        $typeExceptionCode
                    );
                }

                $this->mapAttribute(
                    $reflectionObject,
                    $propertyName,
                    $expectedType,
                    $attributePrefix,
                    $typeExceptionClass,
                    $typeExceptionMessage,
                    $typeExceptionCode,
                    $mappedObject,
                    $value
                );
            } else {
                // Blind mapping
                $property->setValue($mappedObject, $value);
            }
        }

        return $mappedObject;
    }

    /**
     * @param Validate|null $annotation
     * @param stdClass      $object
     * @param string        $propertyName
     *
     * @return bool
     */
    private function checkMandatoryConstraint(Validate $annotation = null, stdClass $object, string $propertyName) : bool
    {
        if (null === $annotation) {
            return true;
        }

        if (!array_key_exists($propertyName, get_object_vars($object)) && $annotation->isMandatory()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $expectedType
     * @param bool   $nullable
     * @param mixed  $value
     *
     * @return bool
     */
    private function checkType(string $expectedType, bool $nullable, $value)
    {
        if (null === $value) {
            if ($nullable) {
                return true;
            }

            return false;
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
                return false;
            } elseif (!is_object($value) && class_exists($expectedType)) {
                // Not an object as expected

                return false;
            }
        } elseif (!is_array($value)) {
            // Array

            return false;
        }

        return true;
    }

    /**
     * @param ReflectionObject $reflectionObject
     * @param string           $propertyName
     * @param string           $expectedType
     * @param string           $attributePrefix
     * @param string           $typeExceptionClass
     * @param string           $typeExceptionMessage
     * @param int              $typeExceptionCode
     * @param mixed            $mappedObject
     * @param mixed            $value
     *
     * @throws Exception
     */
    private function mapAttribute(
        ReflectionObject $reflectionObject,
        string $propertyName,
        string $expectedType,
        string $attributePrefix,
        string $typeExceptionClass,
        string $typeExceptionMessage,
        int $typeExceptionCode,
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
                    $this->mapScalarArrayAttribute(
                        $expectedType,
                        $value,
                        $typeExceptionClass,
                        $typeExceptionMessage,
                        $typeExceptionCode
                    )
                );

                return;
            } else {
                // Object array
                $property->setValue(
                    $mappedObject,
                    $this->mapObjectArrayAttribute(
                        $expectedType,
                        $value,
                        $typeExceptionClass,
                        $typeExceptionMessage,
                        $typeExceptionCode,
                        $attributePrefix
                    )
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
     * @param string $expectedType
     *
     * @param array  $value
     *
     * @param string $typeExceptionClass
     * @param string $typeExceptionMessage
     * @param int    $typeExceptionCode
     *
     * @return array
     * @throws Exception
     */
    private function mapScalarArrayAttribute(
        string $expectedType,
        array $value,
        string $typeExceptionClass,
        string $typeExceptionMessage,
        int $typeExceptionCode
    ) : array
    {
        $expectedType = preg_replace('/\[\]$/', '', $expectedType);

        $values = [];

        foreach ($value as $item) {
            if (!$this->checkType($expectedType, false, $item)) {
                throw new $typeExceptionClass(
                    sprintf($typeExceptionMessage, gettype($item), $expectedType),
                    $typeExceptionCode
                );
            }

            $values[] = $item;
        }

        return $values;
    }

    /**
     * @param string $expectedType
     * @param array  $value
     *
     * @param string $typeExceptionClass
     * @param string $typeExceptionMessage
     * @param int    $typeExceptionCode
     * @param string $attributePrefix
     *
     * @return array
     */
    private function mapObjectArrayAttribute(
        string $expectedType,
        array $value,
        string $typeExceptionClass,
        string $typeExceptionMessage,
        int $typeExceptionCode,
        string $attributePrefix
    ) : array
    {
        $expectedType = preg_replace('/\[\]$/', '', $expectedType);

        $values = [];

        foreach ($value as $item) {
            if (!$this->checkType($expectedType, false, $item)) {
                throw new $typeExceptionClass(
                    sprintf($typeExceptionMessage, gettype($item), $expectedType),
                    $typeExceptionCode
                );
            }

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
