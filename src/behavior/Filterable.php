<?php
namespace filter\behavior;

use Exception;
use filter\MethodFilters;

trait Filterable
{
    protected $_methodFilters = null;

    /**
     * Getter/setter of the `MethodFilters` instance.
     *
     * @param  $methodFilters If `null` return the `MethodFilters` instance, otherwise
     *                        set the `MethodFilters` instance to the passed parameter.
     * @return object
     */
    public function methodFilters($methodFilters = null)
    {
        if ($methodFilters !== null) {
            return $this->_methodFilters = $methodFilters;
        }
        if (!isset($this->_methodFilters)) {
            $this->_methodFilters = new MethodFilters();
        }
        return $this->_methodFilters;
    }

}
