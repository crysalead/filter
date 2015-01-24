<?php
namespace filter\jit\patcher;

use jit\node\NodeDef;
use jit\node\FunctionDef;

class Filter {

    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string The patched file path.
     */
    public function findFile($loader, $class, $file) {
        return $file;
    }

    /**
     * The JIT patcher.
     *
     * @param  instance $node The node to patch.
     * @param  string   $path The file path of the source code.
     * @return instance       The patched node.
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
    protected function _processMethods($nodes) {

        foreach ($nodes as $child) {
            if (!$child->processable) {
                continue;
            }
            if ($child->type !== 'function' || !$child->isMethod || isset($child->visibility['abstract'])) {
                continue;
            }
            $before = new NodeDef("\\filter\\Filter::on(isset(\$this) ? \$this : get_called_class(), __FUNCTION__, func_get_args(), ", 'code');
            $before->parent = $child;
            $before->processable = false;
            $before->namespace = $child->namespace;

            $comma = count($child->args) ? ', ' : '';

            $closure = new FunctionDef("function (\$chain{$comma}" . $child->argsToParams() . ') {');
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
}
