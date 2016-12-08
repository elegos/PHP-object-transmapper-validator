<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use GiacomoFurlan\ObjectTransmapperValidator\Exception\ValidationException;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\ClassWithIntArray;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass\SimpleScalarClass;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestException\MissingMandatoryAttributeException;
use GiacomoFurlan\ObjectTransmapperValidator\Test\TestException\WrongTypeAttributeException;
use GiacomoFurlan\ObjectTransmapperValidator\Transmapper;
use PHPUnit_Framework_TestCase;

/**
 * Class ExceptionTransmapperTest
 * @package GiacomoFurlan\ObjectTransmapperValidator\Test
 */
class ExceptionTransmapperTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Setup the loader for Doctrine Annotations' system
        // (this is usually done by the used framework)
        $loader = include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
        AnnotationRegistry::registerLoader([$loader, "loadClass"]);
    }

    public function testMissingMandatoryAttributeMap()
    {
        $object = (object)[
            "integer" => 1,
            "float" => 1.2,
            "string" => 'whatever'
        ];

        $this->expectException(MissingMandatoryAttributeException::class);
        $this->getTransmapper()->map($object, SimpleScalarClass::class);
    }

    public function testWrongTypeAttributeMap()
    {
        $object = (object)[
            "integer" => 1,
            "boolean" => 'not a boolean',
            "float" => 1.2,
            "string" => 'whatever'
        ];

        $this->expectException(WrongTypeAttributeException::class);
        $this->getTransmapper()->map($object, SimpleScalarClass::class);
    }

    public function testWrongTypeInScalarArrayMap()
    {
        $object = (object)[
            "intArray" => [1, 2, 3, true]
        ];

        $this->expectException(ValidationException::class);
        $this->getTransmapper()->map($object, ClassWithIntArray::class);
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
