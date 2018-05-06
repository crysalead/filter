<?php
namespace Lead\Filter\Spec\Suite;

use Exception;
use InvalidArgumentException;
use Lead\Filter\Filters;

use Kahlan\Jit\ClassLoader;
use Kahlan\Plugin\Double;
use Lead\Filter\Spec\Fixture\Jit\Patcher\Parrot;
use Lead\Filter\Spec\Fixture\Jit\Patcher\Parrot2;
use Lead\Filter\Spec\Fixture\Jit\Patcher\Parrot3;
use Lead\Filter\Spec\Fixture\FilterExample;

describe("Filters", function() {

    beforeEach(function() {

        $this->filter1 = function($next, $message) {
            return "1" . $next($message) . "1";
        };

        $this->filter2 = function($next, $message) {
            return "2" . $next($message) . "2";
        };

        $this->noChain = function($next, $message) {
            return "Hello";
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

                Filters::apply($this->stub, 'filterable', $this->filter1);
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');

            });

            it("applies filters on each call", function() {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');

            });

            it("applies a filter which break the chain", function() {

                Filters::apply($this->stub, 'filterable', $this->noChain);
                expect($this->stub->filterable('World!'))->toBe("Hello");

            });

            it("applies a custom filter", function() {

                allow($this->stub)->toReceive('filterable')->andRun(function() {
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

                Filters::apply(FilterExample::class, 'filterable', $this->filter1);
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');

            });

        });

        describe("::detach()", function() {

            it("detaches a filter", function() {

                $id = Filters::apply($this->stub, 'filterable', $this->filter1);
                expect(Filters::detach($id))->toBeAnInstanceOf('Closure');
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

            it("detaches all filters attached to a callable", function() {

                $id = Filters::apply($this->stub, 'filterable', $this->filter1);
                expect(Filters::detach($this->stub, 'filterable'))->toHaveLength(1);
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

            it("throws an Exception when trying to detach an unexisting filter id", function() {

                $closure = function() { Filters::detach('foo\Bar#0000000046feb0630000000176a1b630::baz|3'); };
                expect($closure)->toThrow(new Exception("Unexisting `'foo\\Bar#0000000046feb0630000000176a1b630::baz|3'` filter id."));

            });

            it("throws an Exception when trying to detach an unexisting filter reference id", function() {

                $closure = function() { Filters::detach('foo\Bar#0000000046feb0630000000176a1b630::baz'); };
                expect($closure)->toThrow(new Exception("Unexisting `'foo\\Bar#0000000046feb0630000000176a1b630::baz'` filter reference id."));

            });

        });

        describe("::filters()", function() {

            it("gets filters attached to a callable", function() {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                $filters = Filters::filters($this->stub, 'filterable');
                expect($filters)->toBeAn('array')->toHaveLength(1);
                expect(reset($filters))->toBeAnInstanceOf('Closure');

            });

        });

        describe("::enable()", function() {

            it("disables the filter system", function() {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                Filters::enable(false);
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

        });

    });

    context("with a class context", function() {

        beforeEach(function() {
            $this->class = Double::classname();
            allow($this->class)->toReceive('::filterable')->andRun(function() {
                return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                    return "Hello {$message}";
                });
            });
        });

        describe("::apply()", function() {

            it("applies a filter and override a parameter", function() {

                $class = $this->class;
                Filters::apply($class, 'filterable', $this->filter1);
                expect($class::filterable('World!'))->toBe('1Hello World!1');

            });

            it("applies a filter and break the chain", function() {

                $class = $this->class;
                Filters::apply($class, 'filterable', $this->noChain);
                expect($class::filterable('World!'))->toBe("Hello");

            });

            it("applies parent classes's filters", function() {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->filter2);
                Filters::apply($subclass, 'filterable', $this->filter1);
                expect($subclass::filterable('World!'))->toBe('12Hello World!21');

            });

            it("applies parent classes's filters using cached filters", function() {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->filter1);
                Filters::apply($subclass, 'filterable', $this->filter2);
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');

            });

            it("invalidates parent cached filters", function() {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->filter1);
                Filters::apply($subclass, 'filterable', $this->filter2);
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');

                Filters::apply($subclass, 'filterable', $this->noChain);
                expect($subclass::filterable('World!'))->toBe("Hello");

            });

            it("applies filters in order", function() {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function() {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($subclass, 'filterable', $this->filter1);
                Filters::apply($subclass, 'filterable', $this->filter2);
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');

            });

        });

        describe("::get()", function() {

            it("exports filters setted as a class level", function() {
                Filters::apply($this->class, 'filterable', $this->filter1);
                $filters = Filters::get();
                expect($filters)->toHaveLength(1);
                expect(isset($filters[$this->class . '::filterable']))->toBe(true);
            });

        });

        describe("::set()", function() {

            it("imports class based filters", function() {
                Filters::set([$this->class . '::filterable' => [$this->filter1]]);
                $filters = Filters::get();
                expect($filters)->toHaveLength(1);
                expect(isset($filters[$this->class . '::filterable']))->toBe(true);
            });

        });
    });

    describe("::apply()", function() {

        it("throws an Exception when trying to apply a filter on an invalid callable string", function() {

            $closure = function() { Filters::apply(null, 'filterable', $this->filter1); };
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

        });

        it("enables/disables the filter JIT patching by using composer compatible autoloader", function() {

            expect(class_exists(Parrot3::class, false))->toBe(false);

            $interceptor = Filters::patch([
                Parrot3::class
            ], ['loader' => ClassLoader::instance()]);

            Filters::apply(Parrot3::class, 'tell', function($next, $message) {
                return $next("HeHe! {$message}");
            });

            $parrot = new Parrot3();
            expect($parrot->tell('Hello'))->toBe('HeHe! Hello');

            $patchers = $interceptor->patchers();
            expect($patchers->exists('filter'))->toBe(true);

            Filters::unpatch();
            expect($patchers->exists('filter'))->toBe(false);

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

            expect(Filters::unpatch())->toBe(true);

        });

    });

    describe("::reset()", function() {

        it("clears all the filters", function() {

            Filters::reset();
            expect(Filters::get())->toBe([]);

        });

    });

});
