<?php

namespace GiacomoFurlan\ObjectTransmapperValidator;

use Doctrine\Common\Annotations\Reader;
use Exception;
use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Exception\TransmappingException;
use ReflectionObject;

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
     * @param mixed  $object
     * @param string $className
     * @param string $attributePrefix used for recursive calls
     *
     * @return mixed (object of class $className)
     * @throws TransmappingException
     * @throws Exception
     */
    public function map($object, string $className, string $attributePrefix = '')
    {
        if (strpos($attributePrefix, '.') === 0) {
            $attributePrefix = substr($attributePrefix, 1);
        }

        $mappedObject = new $className();

        $reflectionObject = new ReflectionObject($mappedObject);

        $classProperties = $reflectionObject->getProperties();

        $objectProperties = get_object_vars($object);

        foreach ($classProperties as $property) {
            /** @var Validate|null $annotation */
            $annotation = $this->reader->getPropertyAnnotation($property, Validate::class);
            $propertyName = $property->getName();

            // Mandatory check
            if (!array_key_exists($propertyName, $objectProperties)) {
                if (null !== $annotation && $annotation->isMandatory()) {
                    $exceptionClass = $annotation->getMandatoryExceptionClass();

                    throw new $exceptionClass(
                        sprintf($annotation->getMandatoryExceptionMessage(), $attributePrefix.'.'.$propertyName),
                        $annotation->getMandatoryExceptionCode()
                    );
                }

                continue;
            }

            $value = $object->$propertyName;
            $property->setAccessible(true);

            // Check the type
            if (null !== $annotation) {
                $expectedType = $annotation->getType();
                $exceptionClass = $annotation->getTypeExceptionClass();
                $isNullable = $annotation->isNullable();

                if (
                    (
                        (($expectedType === 'boolean' || $expectedType === 'bool') && !is_bool($value))
                        || (($expectedType === 'integer' || $expectedType === 'int') && !is_int($value))
                        || (($expectedType === 'float' || $expectedType === 'double') && !is_float($value))
                        || ($expectedType === 'string' && !is_string($value))
                    ) && (null !== $value || !$isNullable)
                ) {
                    throw new $exceptionClass(
                        sprintf($annotation->getTypeExceptionMessage(), gettype($value), $expectedType),
                        $annotation->getTypeExceptionCode()
                    );
                }

                // Map
                if (in_array($expectedType, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string'], true)) {
                    // Scalar value
                    $property->setValue($mappedObject, $value);
                } elseif (null === $value && $isNullable) {
                    $property->setValue($mappedObject, null);
                } elseif (in_array($expectedType, ['boolean[]', 'bool[]', 'integer[]', 'int[]', 'float[]', 'double[]', 'string[]'], true)) {
                    // Array of scalar values

                    if (!is_array($value)) {
                        throw new $exceptionClass(
                            sprintf($annotation->getTypeExceptionMessage(), 'array', gettype($value)),
                            $annotation->getTypeExceptionCode()
                        );
                    }

                    $data = [];
                    foreach ($value as $scalarItem) {
                        if (
                            (($expectedType === 'boolean[]' || $expectedType === 'bool[]') && !is_bool($scalarItem))
                            || (($expectedType === 'integer[]' || $expectedType === 'int[]') && !is_int($scalarItem))
                            || (($expectedType === 'float[]' || $expectedType === 'double[]') && !is_float($scalarItem))
                            || ($expectedType === 'string[]' && !is_string($scalarItem))
                        ) {
                            throw new $exceptionClass(
                                sprintf($annotation->getTypeExceptionMessage(), gettype($scalarItem), $expectedType),
                                $annotation->getTypeExceptionCode()
                            );
                        }

                        $data[] = $scalarItem;
                    }

                    $property->setValue($mappedObject, $data);
                } else {
                    if (preg_match('/[A-Z].*\[\]$/', $expectedType)) {
                        // Array of objects
                        if (!is_array($value)) {
                            throw new $exceptionClass(
                                sprintf($annotation->getTypeExceptionMessage(), $expectedType, gettype($value)),
                                $annotation->getTypeExceptionCode()
                            );
                        }

                        $data = [];
                        foreach ($value as $item) {
                            // Strip array notation from the expected type
                            $itemType = preg_replace('/\[\]$/', '', $expectedType);

                            $data[] = $this->map($item, $itemType, $attributePrefix . '.' . $propertyName);
                        }

                        $property->setValue($mappedObject, $data);
                    } elseif (preg_match('/[A-Z][A-Za-z0-9]+$/', $expectedType)) {
                        // Simple object
                        $property->setValue(
                            $mappedObject,
                            $this->map($value, $expectedType, $attributePrefix.'.'.$propertyName)
                        );
                    } else {
                        throw new TransmappingException(sprintf('Invalid expected type "%s"', $expectedType));
                    }

                }
            } else {
                // Blind mapping
                $property->setValue($mappedObject, $value);
            }
        }

        return $mappedObject;
    }
}
