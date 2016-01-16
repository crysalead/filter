<?php
namespace Lead\Filter\Spec\Suite\Jit\Patcher;

use Exception;
use InvalidArgumentException;
use Lead\Jit\Parser;
use Lead\Filter\Jit\Patcher\Filter;

describe("Filter", function() {

    beforeEach(function() {

        $this->filter = new Filter(['patch' => [
            'My\Name\Space\ClassName',
            'My\Name\Space\MyClass::foo',
            'My\Name\Space\MyClass2' => 'foo',
            'My\Name\Space\MyClass3' => ['foo', 'bar']
        ]]);

    });

    describe("->__construct()", function() {

        it("sets default values", function() {

            $filter = new Filter();
            expect($filter->registered())->toBe(true);

        });

        it("accepts a `true` boolean as `'path'` config", function() {

            $filter = new Filter(['patch' => true]);
            expect($filter->registered())->toBe(true);

        });

        it("accepts an array `'path'` config", function() {

            $filter = new Filter(['patch' => [
                'My\Name\Space\ClassName',
                'My\Name\Space\MyClass::foo',
                'My\Name\Space\MyClass2' => 'foo',
                'My\Name\Space\MyClass3' => ['foo', 'bar']
            ]]);

            expect($filter->registered())->toBe([
                'My\Name\Space\ClassName' => [],
                'My\Name\Space\MyClass' => ['foo' => true],
                'My\Name\Space\MyClass2' => ['foo' => true],
                'My\Name\Space\MyClass3' => ['foo' => true, 'bar' => true]
            ]);

        });

        it("throws an Exception if the `'patch'` config option is invalid", function() {

            $closure = function() { new Filter(['patch' => false]); };
            expect($closure)->toThrow(new InvalidArgumentException("The `'path'` config option must be an array or `true`."));

        });

    });

    describe("->patchable()", function() {

        it("returns `true` on global patching mode", function() {

            $filter = new Filter();
            expect($filter->patchable('My\Name\Space\ClassName'))->toBe(true);

        });

        it("returns `true` when a class is patchable", function() {

            expect($this->filter->patchable('My\Name\Space\ClassName'))->toBe(true);
            expect($this->filter->patchable('My\Name\Space\ClassName2'))->toBe(false);

        });

    });

    describe("->processable()", function() {

        it("returns `true` when a method is processable", function() {

            expect($this->filter->processable('My\Name\Space\ClassName', 'foobar'))->toBe(true);
            expect($this->filter->processable('My\Name\Space\MyClass', 'foo'))->toBe(true);
            expect($this->filter->processable('My\Name\Space\MyClass2', 'foo'))->toBe(true);
            expect($this->filter->processable('My\Name\Space\MyClass3', 'foo'))->toBe(true);
            expect($this->filter->processable('My\Name\Space\MyClass3', 'bar'))->toBe(true);


        });

        it("returns `false` when a method is not processable", function() {

            expect($this->filter->processable('My\Name\Space\MyClass', 'bar'))->toBe(false);
            expect($this->filter->processable('My\Name\Space\MyClass2', 'bar'))->toBe(false);
            expect($this->filter->processable('My\Name\Space\MyClass3', 'foobar'))->toBe(false);

        });

        it("returns `false` when a class for unregistered classes", function() {

            expect($this->filter->processable('My\Unregistered\Space\ClassName', 'foobar'))->toBe(false);

        });

    });

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/Fixture/Jit/Patcher';
            $this->patcher = new Filter();
        });

        it("patches class's methods", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Example.php'));
            $expected = file_get_contents($this->path . '/ExampleProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

        it("patches trait's methods", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/ExampleTrait.php'));
            $expected = file_get_contents($this->path . '/ExampleTraitProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

    });

    describe("->register()", function() {

        it("registers a classes/methods", function() {

            $filter = new Filter();
            expect($filter->registered())->toBe(true);

            $filter->register('My\Name\Space\ClassName');
            expect($filter->registered())->toBe([
                'My\Name\Space\ClassName' => []
            ]);

            $filter->register('My\Name\Space\MyClass3', 'foo');
            expect($filter->registered())->toBe([
                'My\Name\Space\ClassName' => [],
                'My\Name\Space\MyClass3' => ['foo' => true]
            ]);

            $filter->register('My\Name\Space\MyClass3', 'bar');
            expect($filter->registered())->toBe([
                'My\Name\Space\ClassName' => [],
                'My\Name\Space\MyClass3' => ['foo' => true, 'bar' => true]
            ]);

        });

    });

    describe("->unregister()", function() {

        it("unregisters a classes/methods", function() {

            $this->filter->unregister('My\Name\Space\ClassName');
            expect($this->filter->registered())->toBe([
                'My\Name\Space\MyClass' => ['foo' => true],
                'My\Name\Space\MyClass2' => ['foo' => true],
                'My\Name\Space\MyClass3' => ['foo' => true, 'bar' => true]
            ]);

            $this->filter->unregister('My\Name\Space\MyClass3', 'foo');
            expect($this->filter->registered())->toBe([
                'My\Name\Space\MyClass' => ['foo' => true],
                'My\Name\Space\MyClass2' => ['foo' => true],
                'My\Name\Space\MyClass3' => ['bar' => true]
            ]);

        });

        it("throws an Exception if trying to unregister a class in global patching mode", function() {

            $closure = function() {
                $filter = new Filter();
                $filter->unregister('My\Name\Space\ClassName');
            };
            expect($closure)->toThrow(new Exception("Unregistering is unavailable on global patching mode."));

        });

    });

    describe("->registered()", function() {

        it("returns registered classes/methods", function() {

            expect($this->filter->registered())->toBe([
                'My\Name\Space\ClassName' => [],
                'My\Name\Space\MyClass' => ['foo' => true],
                'My\Name\Space\MyClass2' => ['foo' => true],
                'My\Name\Space\MyClass3' => ['foo' => true, 'bar' => true]
            ]);

        });

    });

    describe("->reset()", function() {

        it("resets the filter", function() {

            $this->filter->reset();
            expect($this->filter->registered())->toBe(true);

        });

    });

});
