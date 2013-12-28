<?php
namespace filter;

class Chain implements \Iterator, \Countable
{
    /**
     * The chaining filter array.
     *
     * @var array
     */
    protected $_filters = [];

    /**
     * The name of the method being filtered.
     *
     * @var string
     */
    protected $_method = null;

    /**
     * The params of the method being filtered.
     *
     * @var string
     */
    protected $_params = [];

    /**
     * Construct the collection object
     */
    public function __construct($options = [])
    {
        $defaults = ['filters' => [], 'method' => null, 'params' => []];
        $options += $defaults;
        $this->_filters= $options['filters'];
        $this->_method = $options['method'];
        $this->_params = $options['params'];
    }

    /**
     * Gets the params associated with this filter chain.
     *
     * @return array
     */
    public function params()
    {
        return $this->_params;
    }

    /**
     * Gets the method name associated with this filter chain. This is the method being filtered.
     *
     * @return string
     */
    public function method()
    {
        return $this->_method;
    }

    /**
     * Returns the current item.
     *
     * @return mixed The current item or `false` on failure.
     */
    public function current()
    {
        return current($this->_filters);
    }

    /**
     * Returns the key of the current item.
     *
     * @return scalar Scalar on success or `null` on failure.
     */
    public function key()
    {
        return key($this->_filters);
    }

    /**
     * Provides short-hand convenience syntax for filter chaining.
     *
     * @return mixed Returns the return value of the next filter in the chain.
     */
    public function next()
    {
        next($this->_filters);
        if (($closure = current($this->_filters)) === false) {
            return false;
        }
        $params = $this->_params = func_get_args() + $this->_params;
        array_unshift($params, $this);
        return call_user_func_array($closure, $params);
    }

    /**
     * Rewinds to the first item.
     *
     * @return mixed The current item after rewinding.
     */
    public function rewind()
    {
        reset($this->_filters);
        return current($this->_filters);
    }

    /**
     * Checks if current position is valid.
     *
     * @return boolean `true` if valid, `false` otherwise.
     */
    public function valid()
    {
        return key($this->_filters) !== null;
    }

    /**
     * Returns the number of filters.
     *
     * @return integer
     */
    public function count()
    {
        return count($this->_filters);
    }
}
