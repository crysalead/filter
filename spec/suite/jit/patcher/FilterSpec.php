<?php
namespace filter\spec\suite\jit\patcher;

use jit\Parser;
use filter\jit\patcher\Filter;

describe("Filter", function() {

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/fixture/jit/patcher';
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

});
