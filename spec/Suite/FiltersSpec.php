<?php
namespace Lead\Filter\Spec\Suite;

use Exception;
use InvalidArgumentException;
use Lead\Filter\Filters;

use Kahlan\Jit\Interceptor;
use Kahlan\Plugin\Stub;
use Lead\Filter\Spec\Fixture\Jit\Patcher\Parrot;
use Lead\Filter\Spec\Fixture\Jit\Patcher\Parrot2;
use Lead\Filter\Spec\Fixture\Jit\Patcher\Parrot3;
use Lead\Filter\Spec\Fixture\FilterExample;

describe("Filters", function() {

    beforeEach(function() {

        $this->myPrefix = function($next, $message) {
            $message = "My {$message}";
            return $next($message);
        };

        $this->bePrefix = function($next, $message) {
            $message = "Be {$message}";
            return $next($message);
        };

        $this->noChain = function($next, $message) {
            return "No Man's {$message}";
        };

    });

    afterEach(function() {
        Filters::reset();
    });

    context("with an instance context", function() {

        beforeEach(function() {
            $this->stub = new FilterExample();
        });

        describe("::apply()", function() {

            it("applies a filter", function() {

                Filters::apply($this->stub, 'filterable', $this->myPrefix);
                expect($this->stub->filterable('World!'))->toBe('Hello My World!');

            });

            it("applies filters on each call", function() {

                Filters::apply($this->stub, 'filterable', $this->myPrefix);
                expect($this->stub->filterable('World!'))->toBe('Hello My World!');
                expect($this->stub->filterable('World!'))->toBe('Hello My World!');
                expect($this->stub->filterable('World!'))->toBe('Hello My World!');

            });

            it("applies a filter which break the chain", function() {

                Filters::apply($this->stub, 'filterable', $this->noChain);
                expect($this->stub->filterable('World!'))->toBe("No Man's World!");

            });

            it("applies a custom filter", function() {

                Stub::on($this->stub)->method('filterable', function() {
                    $closure = function($next, $message) {
                        return "Hello {$message}";
                    };
                    $custom = function($next, $message) {
                        $message = "Custom {$message}";
                        return $next($message);
                    };
                    return Filters::run($this, 'filterable', func_get_args(), $closure, [$custom]);
                });
                expect($this->stub->filterable('World!'))->toBe("Hello Custom World!");

            });

            it("applies all filters set on a classname", function() {

                Filters::apply(FilterExample::class, 'filterable', $this->myPrefix);
                expect($this->stub->filterable('World!'))->toBe('Hello My World!');

            });

        });

        describe("::detach()", function() {

            it("detaches a filter", function() {

                $id = Filters::apply($this->stub, 'filterable', $this->myPrefix);
                expect(Filters::detach($id))->toBeAnInstanceOf('Closure');
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

            it("detaches all filters attached to a callable", function() {

                $id = Filters::apply($this->stub, 'filterable', $this->myPrefix);
                expect(Filters::detach($this->stub, 'filterable'))->toHaveLength(1);
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

            it("throws an Exception when trying to detach an unexisting filter id", function() {

                $closure = function() { Filters::detach('foo\Bar#0000000046feb0630000000176a1b630::baz|3'); };
                expect($closure)->toThrow(new Exception("Unexisting `'foo\\Bar#0000000046feb0630000000176a1b630::baz|3'` filter id."));

            });

            it("throws an Exception when trying to detach an unexisting filter id", function() {

                $closure = function() { Filters::detach('foo\Bar#0000000046feb0630000000176a1b630::baz'); };
                expect($closure)->toThrow(new Exception("Unexisting `'foo\\Bar#0000000046feb0630000000176a1b630::baz'` filter reference id."));

            });

        });

        describe("::filters()", function() {

            it("gets filters attached to a callable", function() {

                Filters::apply($this->stub, 'filterable', $this->myPrefix);
                $filters = Filters::filters($this->stub, 'filterable');
                expect($filters)->toBeAn('array')->toHaveLength(1);
                expect(reset($filters))->toBeAnInstanceOf('Closure');

            });

        });

        describe("::enable()", function() {

            it("disables the filter system", function() {

                Filters::apply($this->stub, 'filterable', $this->myPrefix);
                Filters::enable(false);
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

        });

    });

    context("with a class context", function() {

        beforeEach(function() {
            $this->class = Stub::classname();
            Stub::on($this->class)->method('::filterable', function() {
                return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                    return "Hello {$message}";
                });
            });
        });

        describe("::apply()", function() {

            it("applies a filter and override a parameter", function() {

                $class = $this->class;
                Filters::apply($class, 'filterable', $this->myPrefix);
                expect($class::filterable('World!'))->toBe('Hello My World!');

            });

            it("applies a filter and break the chain", function() {

                $class = $this->class;
                Filters::apply($class, 'filterable', $this->noChain);
                expect($class::filterable('World!'))->toBe("No Man's World!");

            });

            it("applies parent classes's filters", function() {

                $class = $this->class;
                $subclass = Stub::classname(['extends' => $class]);
                Stub::on($subclass)->method('::filterable', function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->bePrefix);
                Filters::apply($subclass, 'filterable', $this->myPrefix);
                expect($subclass::filterable('World!'))->toBe('Hello Be My World!');

            });

            it("applies parent classes's filters using cached filters", function() {

                $class = $this->class;
                $subclass = Stub::classname(['extends' => $class]);
                Stub::on($subclass)->method('::filterable', function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->bePrefix);
                Filters::apply($subclass, 'filterable', $this->myPrefix);
                expect($subclass::filterable('World!'))->toBe('Hello Be My World!');
                expect($subclass::filterable('World!'))->toBe('Hello Be My World!');

            });

            it("invalidates parent cached filters", function() {

                $class = $this->class;
                $subclass = Stub::classname(['extends' => $class]);
                Stub::on($subclass)->method('::filterable', function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->bePrefix);
                Filters::apply($subclass, 'filterable', $this->myPrefix);
                expect($subclass::filterable('World!'))->toBe('Hello Be My World!');

                Filters::apply($subclass, 'filterable', $this->noChain);
                expect($subclass::filterable('World!'))->toBe("No Man's My World!");

            });

        });

        describe("::get()", function() {

            it("exports filters setted as a class level", function() {
                Filters::apply($this->class, 'filterable', $this->myPrefix);
                $filters = Filters::get();
                expect($filters)->toHaveLength(1);
                expect(isset($filters[$this->class . '::filterable']))->toBe(true);
            });

        });

        describe("::set()", function() {

            it("imports class based filters", function() {
                Filters::set([$this->class . '::filterable' => [$this->myPrefix]]);
                $filters = Filters::get();
                expect($filters)->toHaveLength(1);
                expect(isset($filters[$this->class . '::filterable']))->toBe(true);
            });

        });
    });

    describe("::apply()", function() {

        it("throws an Exception when trying to apply a filter on an invalid callable string", function() {

            $closure = function() { Filters::apply(null, 'filterable', $this->myPrefix); };
            expect($closure)->toThrow(new InvalidArgumentException("The provided callable is invalid."));

        });

    });

    describe("::patch()", function() {

        it("enables/disables the filter JIT patching using the Khalan's interceptor", function() {

            expect(class_exists(Parrot::class, false))->toBe(false);

            $interceptor = Filters::patch([
                Parrot::class
            ]);

            Filters::apply(Parrot::class, 'tell', function($next, $message) {
                return $next("HeHe! {$message}");
            });

            $parrot = new Parrot();
            expect($parrot->tell('Hello'))->toBe('HeHe! Hello');

            $patchers = $interceptor->patchers();
            expect($patchers->exists('filter'))->toBe(true);

            Filters::unpatch();
            expect($patchers->exists('filter'))->toBe(false);

        });

        it("enables/disables the filter JIT patching by patching an autodetected composer autoloader", function() {

            $previous = Interceptor::instance();

            Interceptor::unpatch();

            expect(class_exists(Parrot2::class, false))->toBe(false);

            $interceptor = Filters::patch([
                Parrot2::class => 'tell'
            ]);

            Filters::apply(Parrot2::class, 'tell', function($next, $message) {
                return $next("HeHe! {$message}");
            });

            $parrot = new Parrot2();
            expect($parrot->tell('Hello'))->toBe('HeHe! Hello');

            $patchers = $interceptor->patchers();
            expect($patchers->exists('filter'))->toBe(true);

            Filters::unpatch();
            expect($patchers->exists('filter'))->toBe(false);

            Interceptor::load($previous);

        });

        it("enables/disables the filter JIT patching by using composer compatible autoloader", function() {

            $previous = Interceptor::instance();

            Interceptor::unpatch();

            expect(class_exists(Parrot3::class, false))->toBe(false);

            $interceptor = Filters::patch([
                Parrot3::class
            ], ['loader' => Interceptor::composer()]);

            Filters::apply(Parrot3::class, 'tell', function($next, $message) {
                return $next("HeHe! {$message}");
            });

            $parrot = new Parrot3();
            expect($parrot->tell('Hello'))->toBe('HeHe! Hello');

            $patchers = $interceptor->patchers();
            expect($patchers->exists('filter'))->toBe(true);

            Filters::unpatch();
            expect($patchers->exists('filter'))->toBe(false);

            Interceptor::load($previous);

        });

        it("throws an exception when no autoloader are available", function() {

            $loaders = spl_autoload_functions();

            foreach ($loaders as $loader) {
                spl_autoload_unregister($loader);
            }

            try {
                Filters::patch();
                $success = true;
            } catch (Exception $e) {
                $success = $e->getMessage();
            };

            foreach ($loaders as $loader) {
                spl_autoload_register($loader);
            }

            expect($success)->toBe('Unable to find a valid autoloader to apply the JIT filter patcher.');

        });

    });

    describe("::unpatch()", function() {

        it("bails out when the filter JIT patcher is not enabled", function() {

            Filters::unpatch();

        });

    });

    describe("::reset()", function() {

        it("clears all the filters", function() {

            Filters::reset();
            expect(Filters::get())->toBe([]);

        });

    });

});
