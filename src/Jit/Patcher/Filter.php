<?php
namespace Lead\Filter\Jit\Patcher;

use Exception;
use InvalidArgumentException;
use Lead\Jit\Node\NodeDef;
use Lead\Jit\Node\FunctionDef;

class Filter {

    protected $_patchable = [];

    public function __construct($config = [])
    {
        $defaults = [
            'patch' => true
        ];
        $config += $defaults;

        if ($config['patch'] === true) {
            $this->_patchable = true;
            return;
        }
        if (!is_array($config['patch'])) {
            throw new InvalidArgumentException("The `'path'` config option must be an array or `true`.");
        }
        foreach ($config['patch'] as $class => $method) {
            if (is_array($method)) {
                foreach ($method as $key => $value) {
                    $this->register($class, $value);
                }
                continue;
            }
            if (strpos($method, '::')) {
                list($class, $method) = explode('::', $method) + [null, null];
            } else if (is_numeric($class)) {
                $class = $method;
                $method = null;
            }
            $this->register($class, $method);
        }
    }

    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        return $file;
    }

    /**
     * The JIT patchable checker.
     *
     * @param  string  $class The fully-namespaced class name to check.
     * @return boolean
     */
    public function patchable($class)
    {
        if ($this->_patchable === true) {
            return true;
        }
        return isset($this->_patchable[$class]);
    }

    /**
     * The JIT patcher.
     *
     * @param  object|null $node  The node to patch.
     * @param  string      $path  The file path of the source code.
     * @return object|null        The patched node.
     */
    public function process($node, $path = null)
    {
        $this->_processTree($node->tree);
        return $node;
    }

    /**
     * Helper for `Filter::process`.
     *
     * @param array $nodes A node array to patch.
     */
    protected function _processTree($nodes)
    {
        foreach ($nodes as $node) {
            if ($node->hasMethods && $node->type !== 'interface') {
                $this->_processMethods($node->tree);
            } elseif (count($node->tree)) {
                $this->_processTree($node->tree);
            }
        }
    }

    /**
     * Helper for `Filter::process`.
     *
     * @param instance The node to patch.
     */
    protected function _processMethods($nodes)
    {
        foreach ($nodes as $child) {
            if (!$child->processable) {
                continue;
            }
            if ($child->type !== 'function' || !$child->isMethod || isset($child->visibility['abstract'])) {
                continue;
            }
            $namespace = $child->namespace ? $child->namespace->name . '\\' : '';
            $class = $namespace . $child->parent->name;

            if (!$this->processable($class, $child->name)) {
                continue;
            }
            $params = $child->argsToParams();
            $args = $params ? "[{$params}] + " : '';
            $before = new NodeDef("return \\Lead\Filter\\Filters::run(isset(\$this) ? \$this : get_called_class(), __FUNCTION__, " . $args . "func_get_args(), ", 'code');
            $before->parent = $child;
            $before->processable = false;
            $before->namespace = $child->namespace;

            $comma = count($child->args) ? ', ' : '';

            $closure = new FunctionDef("function (\$next{$comma}" . $params . ") {");
            $closure->parent = $child;
            $closure->namespace = $child->namespace;
            $closure->isClosure = true;
            $closure->tree = $child->tree;
            foreach($closure->tree as $item) {
                $item->parent = $closure;
            }
            $closure->close = '});';

            $child->tree = [];
            $child->tree[] = $before;
            $child->tree[] = $closure;
        }
    }

    public function processable($class, $method)
    {
        if ($this->_patchable === true) {
            return true;
        }
        if (!isset($this->_patchable[$class])) {
            return false;
        }
        return !$this->_patchable[$class] || isset($this->_patchable[$class][$method]);
    }

    public function register($class, $method = null)
    {
        if ($this->_patchable === true) {
            $this->_patchable = [];
        }
        if ($method) {
            $this->_patchable[$class][$method] = true;
        } else {
            $this->_patchable[$class] = [];
        }
    }

    public function registered()
    {
        return $this->_patchable;
    }

    public function unregister($class, $method = null)
    {
        if ($this->_patchable === true) {
            throw new Exception('Unregistering is unavailable on global patching mode.');
        }
        if (func_num_args() === 1) {
            unset($this->_patchable[$class]);
            return;
        }
        unset($this->_patchable[$class][$method]);
    }

    public function reset()
    {
        $this->_patchable = true;
    }
}
