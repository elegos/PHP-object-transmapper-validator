<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithFloatArray;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithInnerMappedModel;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithIntArray;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithMappedModel;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithNullableAttribute;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithRegex;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithSimpleScalarClassArray;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithSimpleScalarClassInside;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithUnmappedAttributes;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\SimpleScalarClass;
use GiacomoFurlan\ObjectTransmapperValidator\Transmapper;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * Class TransmapperTest
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test
 */
class SuccessfulTransmapperTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Setup the loader for Doctrine Annotations' system
        // (this is usually done by the used framework)
        $loader = include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
        AnnotationRegistry::registerLoader([$loader, "loadClass"]);
    }

    public function testOneLevelScalarMap()
    {
        $transmapper = $this->getTransmapper();

        $object = $this->getSimpleScalarModel();

        /** @var SimpleScalarClass $mapped */
        $mapped = $transmapper->map($object, SimpleScalarClass::class);

        static::assertEquals(1, $mapped->getInteger());
        static::assertNull($mapped->getInt());
        static::assertEquals(true, $mapped->isBoolean());
        static::assertNull($mapped->isBool());
        static::assertEquals(1.2, $mapped->getFloat());
        static::assertNull($mapped->getDouble());
        static::assertEquals('whatever', $mapped->getString());
    }

    public function testOneLevelUnmappedAttributesMap()
    {
        $transmapper = $this->getTransmapper();

        $object = $this->getSimpleScalarModel();
        $object->one = 'Not null';

        /** @var ClassWithUnmappedAttributes $mapped */
        $mapped = $transmapper->map($object, ClassWithUnmappedAttributes::class);

        static::assertEquals(true, $mapped->isBoolean());
        static::assertEquals('Not null', $mapped->getOne());
        static::assertNull($mapped->getTwo());
        static::assertNull($mapped->getThree());
    }

    public function testMultiLevelWithClassMap()
    {
        $transmapper = $this->getTransmapper();

        $object = (object) [
            'innerClass' => $this->getSimpleScalarModel()
        ];

        class_exists(Validate::class);

        /** @var ClassWithSimpleScalarClassInside $mapped */
        $mapped = $transmapper->map($object, ClassWithSimpleScalarClassInside::class);

        static::assertNotNull($mapped->getInnerClass());
        static::assertEquals(1, $mapped->getInnerClass()->getInteger());
    }

    public function testMultiLevelWithScalarArrayMap()
    {
        $transmapper = $this->getTransmapper();

        $object = (object) [
            'intArray' => [1, 2, 3, 4]
        ];

        /** @var ClassWithIntArray $mapped */
        $mapped = $transmapper->map($object, ClassWithIntArray::class);

        static::assertEquals(1, $mapped->getIntArray()[0]);
        static::assertEquals(2, $mapped->getIntArray()[1]);
        static::assertEquals(3, $mapped->getIntArray()[2]);
        static::assertEquals(4, $mapped->getIntArray()[3]);
    }

    public function testMultiLevelWithClassArrayMap()
    {
        $transmapper = $this->getTransmapper();

        $object = (object) [
            'innerScalarArray' => [$this->getSimpleScalarModel(), $this->getSimpleScalarModel()]
        ];

        /** @var ClassWithSimpleScalarClassArray $result */
        $result = $transmapper->map($object, ClassWithSimpleScalarClassArray::class);

        static::assertCount(2, $result->getInnerScalarArray());
    }

    public function testNullableTypeMap()
    {
        $object = (object) [
            'nullableInt' => null,
        ];
        /** @var ClassWithNullableAttribute $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithNullableAttribute::class);
        static::assertEquals(null, $mapped->getNullableInt());

        $object->nullableInt = 23;
        /** @var ClassWithNullableAttribute $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithNullableAttribute::class);
        static::assertEquals(23, $mapped->getNullableInt());
    }

    public function testRegexConstraintMap()
    {
        $object = (object) [
            'string' => 'fiveC',
            'int' => 23
        ];

        /** @var ClassWithRegex $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithRegex::class);
        static::assertEquals('fiveC', $mapped->getString());
        static::assertEquals(23, $mapped->getInt());
    }

    public function testMappedModel()
    {
        $object = (object) [
            'mapped' => 'whatever'
        ];

        /** @var ClassWithMappedModel $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithMappedModel::class);

        static::assertEquals('whatever', $mapped->getMapped());
        static::assertNull($mapped->getNotMapped());
        static::assertTrue($mapped->isMapped('mapped'));
        static::assertFalse($mapped->isMapped('notMapped'));

        $outer = (object) [
            'inner' => $object,
            '_mapped' => 'this will be ignored'
        ];

        /** @var ClassWithInnerMappedModel $mapped */
        $mapped = $this->getTransmapper()->map($outer, ClassWithInnerMappedModel::class);
        $inner = $mapped->getInner();

        static::assertEquals('whatever', $inner->getMapped());
        static::assertNull($inner->getNotMapped());
        static::assertTrue($inner->isMapped('mapped'));
        static::assertFalse($inner->isMapped('notMapped'));
    }

    /**
     * Check for special float case (integers are still valid floats)
     */
    public function testFloatValue()
    {
        $object = $this->getSimpleScalarModel();
        $object->float = 1;

        /** @var SimpleScalarClass $mapped */
        $mapped = $this->getTransmapper()->map($object, SimpleScalarClass::class);

        static::assertEquals(1, $mapped->getFloat());

        $object = (object)[
            'floatArray' => [1, 1.1, 2],
        ];

        /** @var ClassWithFloatArray $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithFloatArray::class);
        static::assertEquals($mapped->getFloatArray(), $object->floatArray);
    }

    /**
     * @return stdClass
     */
    private function getSimpleScalarModel() {
        return (object)[
            "integer" => 1,
            "boolean" => true,
            "float" => 1.2,
            "string" => 'whatever'
        ];
    }

    /**
     * @return Transmapper
     */
    private function getTransmapper() : Transmapper
    {
        $reader = new AnnotationReader();
        return new Transmapper($reader);
    }
}
