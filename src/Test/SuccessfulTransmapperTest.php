<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use GiacomoFurlan\ObjectTransmapperValidator\Exception\ValidationException;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithIntArray;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithNullableAttribute;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithRegex;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithSimpleScalarClassArray;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithSimpleScalarClassInside;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithUnmappedAttributes;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\SimpleScalarClass;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestException\MissingMandatoryAttributeException;
use GiacomoFurlan\ObjectTransmapperValidator\Transmapper;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * Class TransmapperTest
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test
 */
class SuccessfulTransmapperTest extends PHPUnit_Framework_TestCase
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

        $this->assertEquals(1, $mapped->getInteger());
        $this->assertNull($mapped->getInt());
        $this->assertEquals(true, $mapped->isBoolean());
        $this->assertNull($mapped->isBool());
        $this->assertEquals(1.2, $mapped->getFloat());
        $this->assertNull($mapped->getDouble());
        $this->assertEquals('whatever', $mapped->getString());
    }

    public function testOneLevelUnmappedAttributesMap()
    {
        $transmapper = $this->getTransmapper();

        $object = $this->getSimpleScalarModel();
        $object->one = 'Not null';

        /** @var ClassWithUnmappedAttributes $mapped */
        $mapped = $transmapper->map($object, ClassWithUnmappedAttributes::class);

        $this->assertEquals(true, $mapped->isBoolean());
        $this->assertEquals('Not null', $mapped->getOne());
        $this->assertNull($mapped->getTwo());
        $this->assertNull($mapped->getThree());
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

        $this->assertNotNull($mapped->getInnerClass());
        $this->assertEquals(1, $mapped->getInnerClass()->getInteger());
    }

    public function testMultiLevelWithScalarArrayMap()
    {
        $transmapper = $this->getTransmapper();

        $object = (object) [
            'intArray' => [1, 2, 3, 4]
        ];

        /** @var ClassWithIntArray $mapped */
        $mapped = $transmapper->map($object, ClassWithIntArray::class);

        $this->assertEquals(1, $mapped->getIntArray()[0]);
        $this->assertEquals(2, $mapped->getIntArray()[1]);
        $this->assertEquals(3, $mapped->getIntArray()[2]);
        $this->assertEquals(4, $mapped->getIntArray()[3]);
    }

    public function testMultiLevelWithClassArrayMap()
    {
        $transmapper = $this->getTransmapper();

        $object = (object) [
            'innerScalarArray' => [$this->getSimpleScalarModel(), $this->getSimpleScalarModel()]
        ];

        $transmapper->map($object, ClassWithSimpleScalarClassArray::class);
    }

    public function testNullableTypeMap()
    {
        $object = (object) [
            'nullableInt' => null,
        ];
        /** @var ClassWithNullableAttribute $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithNullableAttribute::class);
        $this->assertEquals(null, $mapped->getNullableInt());

        $object->nullableInt = 23;
        /** @var ClassWithNullableAttribute $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithNullableAttribute::class);
        $this->assertEquals(23, $mapped->getNullableInt());
    }

    public function testRegexConstraintMap()
    {
        $object = (object) [
            'string' => 'fiveC',
            'int' => 23
        ];

        /** @var ClassWithRegex $mapped */
        $mapped = $this->getTransmapper()->map($object, ClassWithRegex::class);
        $this->assertEquals('fiveC', $mapped->getString());
        $this->assertEquals(23, $mapped->getInt());
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
