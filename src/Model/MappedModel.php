<?php

namespace GiacomoFurlan\ObjectTransmapperValidator\Model;

/**
 * Class MappedModel
 * @package GiacomoFurlan\ObjectTransmapperValidator\Model
 */
class MappedModel
{
    private $_mapped;

    public function setMapped($attributeName) : bool
    {
        if ($attributeName === '_mapped') {
            return false;
        }

        if (!is_array($this->_mapped)) {
            $this->_mapped = [];
        }

        $this->_mapped[$attributeName] = true;

        return true;
    }

    /**
     * @param string $attribute
     *
     * @return bool
     */
    public function isMapped(string $attribute) : bool
    {
        if (!is_array($this->_mapped)) {
            return false;
        }

        return array_key_exists($attribute, $this->_mapped);
    }
}
