<?php

namespace GiacomoFurlan\ObjectTransmapperValidator;

use Doctrine\Common\Annotations\Reader;
use Exception;
use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Dto\PropertyInfo;
use GiacomoFurlan\ObjectTransmapperValidator\Model\MappedModel;
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
     * @param array    $overrides ['dot.notation.attribute' => ['regex' => '', ...]]
     *
     * @return mixed (object of class $className)
     * @throws Exception
     */
    public function map(stdClass $object, string $className, ...$overrides)
    {
        return $this->internalMap($object, $className, '', $overrides);
    }

    /**
     * @param stdClass $object
     * @param string   $className
     * @param string   $attributePrefix used for recursive calls
     * @param array    $overrides       [['dot.notation.attribute' => ['regex' => '', ...]], ...]
     *
     * @return mixed (object of class $className)
     * @throws Exception
     */
    private function internalMap(stdClass $object, string $className, string $attributePrefix = '', $overrides)
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
            $propertyInfo = new PropertyInfo($className, $propertyName);

            $this->overrideAnnotationAttributes($annotation, $attributePrefix, $propertyName, $overrides);

            // Mandatory check
            $this->checkMandatoryConstraint($annotation, $propertyInfo, $object, $propertyName, $attributePrefix);

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
            $this->checkTypeConstraint($annotation, $propertyInfo, $value);

            // Regex check
            $this->checkRegexConstraint($annotation, $value);

            $expectedType = $annotation->getType();
            $this->mapAttribute(
                $reflectionObject,
                $annotation,
                $propertyInfo,
                $propertyName,
                $expectedType,
                $attributePrefix,
                $mappedObject,
                $value,
                $overrides
            );
        }

        return $mappedObject;
    }

    /**
     * @param Validate|null $annotation
     * @param string        $attributePrefix
     * @param string        $propertyName
     * @param array         $overrides
     */
    private function overrideAnnotationAttributes(
        Validate $annotation = null,
        string $attributePrefix,
        string $propertyName,
        array $overrides
    ): void {
        if ($annotation === null) {
            return;
        }

        $dotNotation = '' === $attributePrefix ? $propertyName : $attributePrefix.'.'.$propertyName;

        $availableOverrides = [];
        foreach ($overrides as $override) {
            if (0 === count($override)) {
                continue;
            }

            if (array_key_exists($dotNotation, $override)) {
                $availableOverrides[$dotNotation] = $override[$dotNotation];
            }
        }

        if (array_key_exists($dotNotation, $availableOverrides)) {
            foreach ($availableOverrides[$dotNotation] as $override => $value) {
                $annotation->$override = $value;
            }
        }
    }

    /**
     * @param Validate|null $annotation
     * @param PropertyInfo  $propertyInfo
     * @param stdClass      $object
     * @param string        $propertyName
     * @param string        $attributePrefix
     *
     * @throws Exception
     */
    private function checkMandatoryConstraint(
        Validate $annotation = null,
        PropertyInfo $propertyInfo,
        stdClass $object,
        string $propertyName,
        string $attributePrefix
    ): void {
        if (
            null !== $annotation
            && !array_key_exists($propertyName, get_object_vars($object))
            && $annotation->isMandatory()
        ) {
            $mandatoryExceptionClass = $annotation->getMandatoryExceptionClass();

            $missingField = '' !== $attributePrefix ? $attributePrefix.'.'.$propertyName : $propertyName;

            throw new $mandatoryExceptionClass(
                sprintf(
                    '%s: %s',
                    $propertyInfo,
                    sprintf($annotation->getMandatoryExceptionMessage(), $missingField)
                ),
                $annotation->getMandatoryExceptionCode()
            );
        }
    }

    /**
     * @param Validate     $annotation
     * @param PropertyInfo $propertyInfo
     * @param mixed        $value
     * @param string|null  $forcedExpectedType
     * @param bool|null    $forceNullable
     *
     * @throws Exception
     */
    private function checkTypeConstraint(
        Validate $annotation,
        PropertyInfo $propertyInfo,
        $value,
        $forcedExpectedType = null,
        $forceNullable = null
    ): void {
        $expectedType = $forcedExpectedType ?? $annotation->getType();
        $nullable = $forceNullable ?? $annotation->isNullable();
        $typeExceptionClass = $annotation->getTypeExceptionClass();

        $foundType = is_object($value) ? get_class($value) : gettype($value);

        $exception = new $typeExceptionClass(
            sprintf(
                '%s: %s',
                $propertyInfo,
                sprintf($annotation->getTypeExceptionMessage(), $foundType, $expectedType)
            ),
            $annotation->getTypeExceptionCode()
        );

        if (null === $value) {
            if (!$nullable) {
                throw $exception;
            }

            return;
        }

        $isArray = $this->isArray($expectedType);

        if (!$isArray) {
            // Single value

            if (
                ('string' === $expectedType && !is_string($value))
                || (in_array($expectedType, ['bool', 'boolean'], true) && !is_bool($value))
                || (in_array($expectedType, ['float', 'double'], true) && !is_float($value)) && !is_int($value)
                || (in_array($expectedType, ['int', 'integer'], true) && !is_int($value))
            ) {
                // Not a scalar value as expected

                throw $exception;
            }

            if (!is_object($value) && class_exists($expectedType)) {
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
    private function checkRegexConstraint(Validate $annotation, $value): void
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
     * @param PropertyInfo     $propertyInfo
     * @param string           $propertyName
     * @param string           $expectedType
     * @param string           $attributePrefix
     * @param mixed            $mappedObject
     * @param mixed            $value
     * @param array            $overrides
     *
     * @throws Exception
     */
    private function mapAttribute(
        ReflectionObject $reflectionObject,
        Validate $annotation,
        PropertyInfo $propertyInfo,
        string $propertyName,
        string $expectedType,
        string $attributePrefix,
        $mappedObject,
        $value,
        array $overrides
    ): void {
        $property = $reflectionObject->getProperty($propertyName);
        $property->setAccessible(true);

        if ($this->isArray($expectedType)) {
            if ($this->isScalarArray($expectedType)) {
                // Scalar array
                $this->setValue(
                    $property,
                    $propertyName,
                    $mappedObject,
                    $this->mappedScalarArrayAttribute($annotation, $propertyInfo, $expectedType, $value)
                );

                return;
            }

            // Object array
            $this->setValue(
                $property,
                $propertyName,
                $mappedObject,
                $this->mappedObjectArrayAttribute($annotation, $propertyInfo, $expectedType, $value, $attributePrefix, $overrides)
            );
        } else {
            // Simple scalar value
            if ($this->isScalar($expectedType)) {
                // Fix possible float-to-int conversions (int is still a valid float value)
                if ($this->isFloat($expectedType)) {
                    $value = (float) $value;
                }
                $this->setValue($property, $propertyName, $mappedObject, $value);
            } else {
                // Object
                $this->setValue(
                    $property,
                    $propertyName,
                    $mappedObject,
                    $this->internalMap($value, $expectedType, $attributePrefix . '.' . $propertyName, $overrides)
                );
            }
        }
    }

    private function setValue(ReflectionProperty $property, string $propertyName, $mappedObject, $value): void
    {
        if ($mappedObject instanceof MappedModel) {
            if (!$mappedObject->setMapped($propertyName)) {
                return;
            }
        }

        $property->setValue($mappedObject, $value);
    }

    /**
     * @param Validate     $annotation
     * @param PropertyInfo $propertyInfo
     * @param string       $expectedType
     * @param array        $value
     *
     * @return array
     * @throws Exception
     */
    private function mappedScalarArrayAttribute(
        Validate $annotation,
        PropertyInfo $propertyInfo,
        string $expectedType,
        array $value
    ) : array {
        $expectedType = preg_replace('/\[\]$/', '', $expectedType);
        $isFloat = $this->isFloat($expectedType);

        $values = [];
        foreach ($value as $item) {
            $this->checkTypeConstraint($annotation, $propertyInfo, $item, $expectedType, false);

            $values[] = $isFloat ? (float) $item : $item;
        }

        return $values;
    }

    /**
     * @param Validate     $annotation
     * @param PropertyInfo $propertyInfo
     * @param string       $expectedType
     * @param array        $value
     * @param string       $attributePrefix
     * @param array        $overrides
     *
     * @return array
     * @throws Exception
     */
    private function mappedObjectArrayAttribute(
        Validate $annotation,
        PropertyInfo $propertyInfo,
        string $expectedType,
        array $value,
        string $attributePrefix,
        array $overrides
    ) : array
    {
        $expectedType = preg_replace('/\[\]$/', '', $expectedType);

        $values = [];

        foreach ($value as $item) {
            $this->checkTypeConstraint($annotation, $propertyInfo, $item, $expectedType, false);

            $values[] = $this->internalMap($item, $expectedType, $attributePrefix.'.'.$expectedType.'[]', $overrides);
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

    private function isFloat(string $type) : bool
    {
        return $type === 'float';
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
