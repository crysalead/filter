# Filter - Method Filtering System.

[![Build Status](https://travis-ci.org/crysalead/filter.png?branch=master)](https://travis-ci.org/crysalead/filter) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/filter/badges/coverage.png?s=50b3c56bd62e6a14c1c15b7c7f5c26584ff2bf7a)](https://scrutinizer-ci.com/g/crysalead/filter/)

Method filtering is an alternative to event-driven architectures. It provide a way to inject/override some logic in the program flow without polluting too much the original source code.

There's a couple of different existing approches which try to bring the AOP concepts in PHP.
 * [AOP](https://github.com/AOP-PHP/AOP) (PECL extension)
 * [Go!](https://github.com/lisachenko/go-aop-php)
 * [Ray Aop](https://github.com/ray-di/Ray.Aop)

Another AOP alternative is the well known middleware pattern. However I found the [lihtium](https://github.com/UnionOfRAD/lithium) implementation simpler and more intuitive to use.

Anyhow all this approaches aims to provide the following control on methods:

```
        │                ▲
        │                │
 ┌──────┼────────────────┼──────┐
 │      │    Filter 1    │      │
 │      │                │      │
 │ ┌────┼────────────────┼────┐ │
 │ │    │    Filter 2    │    │ │
 │ │    │                │    │ │
 │ │┌───┼────────────────┼───┐│ │
 │ ││   │ Implementation │   ││ │
 │ ││   ▼                │   ││ │
 │ ││                        ││ │
 │ │└────────────────────────┘│ │
 │ └──────────────────────────┘ │
 └──────────────────────────────┘
```

## The example

So let's take the following code example:

```php
class Home
{
    public static function version()
    {
        return '1.0.0';
    }

    public function enter($name)
    {
        return "Welcome {$name}!";
    }
}

```

At this point it's not possible to change method's behavior like in JavaScript or any other more permissive language. At this point we can make the above method in two ways. First by manually adding some boilerplate code to make your methods filterable or by simply enabling some Just In Time patching which will do the rewriting on the fly.

## The manually way

To make methods of the above example filterable, we will need to add some boilerplate code like so:

```php
namespace City;

use Lead\Filter\Filters;

class Home {

    public static function version()
    {
        Filters::run(get_called_class(), __FUNCTION__, [], function($next) {
            return '1.0.0'; // Your inchanged code here
        });
    }

    public function enter($name)
    {
        Filters::run($this, __FUNCTION__, [$name], function($next, $name) {
            return "Welcome {$name}!"; // Your inchanged code here
        });
    }
}

```

All the method logic has been wrapped up in a closure which also requires a mandatory `$next` parameter as first parameter. `$next` represents the chain of filters to apply and will be used in filters to run the next appliable filter.

Once the code rewrited, it's now possible to setup filters:

```php
use Lead\Filter\Filters;

Filters::apply('city\Home::version', function($next) {
    $version = $next();
    return "Version: {$version}";
});

$home = new Home();
Filters::apply([$home, 'enter'], function($next, $name) {
    $name = "Mister {$name}";
    return $next($name);
});

echo "You are using the Home " . Home::version();
echo $home->enter('Bob');
```

And it will produce:
```
You are using the Home Version 1.0.0
Welcome Mister Bob
```

## The automatic way

The second option is to make this rewriting step automatic and transparent for the user.

To do so, the only step is to run `Filters::patch()` as soon as possible. For example just after the composer autoloader include:

```php
include __DIR__ . '/../vendor/autoload.php';

use Lead\Filter\Filters;

Filters::patch(true);
```

Note: The patching will only work for classes loaded by the composer autoloader. So if some classes is included using `require` or `include` or has already been loaded before the `Filters::patch(true)` call, it won't be patched.

Using `Filters::patch(true)` is the no brainer way to setup the patcher but you should keep in mind that all your code as well as your vendor code will be patched. Even if patched classes are cached once patched, having filters for all methods can be time consuming.

So the prefered approach is to only patch needed files like in the following example:

```php
Filters::patch([
 'City\Home',
 'An\Example\ClassName::foo',
 'A\Second\Example\ClassName' => ['foo', 'bar'],
]);
```

It's therefore possible to makes your own methods filterable as well as vendor methods.

## API

### Make a method filterable

Either manually with:

```php
Filters::run($context, $method, $args, $closure);
```

Or automatically:

```php
Filters::patch(['City\Home']);
```

### Apply a filter to a class or an instance

```php
$id = Filters::apply($callable, $closure);
```

### Detach a filter from a class or an instance

Detach all filters associated to a callable:
```php
Filters::detach($callable);
```

Detach a specific filter only:
```php
Filters::detach($id);
```

### Export/Restore the filtering system.

Getter:
```php
$filters = Filters::get();
```

Setter:
```php
Filters::set($filters);
```

### Clearing the registred closure & applied filters.

```php
Filters::reset();
```

Note: It also detaches all filters attached statically (i.e it doesn't affect filters on intance's methods).

### Enable/Disable the filter system globaly

```php
Filters::enable(); // Enable
Filters::enable(false); // Disable
```

Note: It doesn't detach any filters but simply bypasses filters on `Filters::run()`.

## FAQ

- **Is it possible to apply a filter for all instances ?** Yes, for such behavior, you need to set your filter using the class name string as context.

- **If sub class inherit from filters setted at a parent class level ?** Yes.
