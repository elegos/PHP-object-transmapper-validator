<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Test\TestClass;

use GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate;
use stdClass;

class ClassWithArrayOfStdClass
{
    /**
     * @var stdClass[]
     * @Validate(type="stdClass[]", mandatory=true)
     */
    private $arrayOfObjects;

    /**
     * @return stdClass[]
     */
    public function getArrayOfObjects(): array
    {
        return $this->arrayOfObjects;
    }
}
