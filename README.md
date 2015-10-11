# Filter - Method Filtering System.

[![Build Status](https://travis-ci.org/crysalead/filter.png?branch=master)](https://travis-ci.org/crysalead/filter) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/filter/badges/coverage.png?s=50b3c56bd62e6a14c1c15b7c7f5c26584ff2bf7a)](https://scrutinizer-ci.com/g/crysalead/filter/)

Method filtering is an alternative to event-driven architectures. It provide a way to inject/override some logic in the program flow without polluting too much the original source code.

There's a couple of different existing approches which try to bring the AOP concepts in PHP.
 * [AOP](https://github.com/AOP-PHP/AOP) (PECL extension)
 * [Go!](https://github.com/lisachenko/go-aop-php)
 * [Ray Aop](https://github.com/koriym/Ray.Aop)

However I still think that the [lihtium](https://github.com/UnionOfRAD/lithium) implementation is simpler to implements and the overhead still minimal when AOP is required for a couple of methods in a projet.

Like the lithium's version this implementation also require some boilerplate code to make filterable methods. Event if there's some slighly differences in the API the logic is roughly the same.

## Example

So let's take a simple example:

```php
class Home {

	public static function version() {
		return '1.0.0';
	}

	public function enter($name) {
		return "Welcome {$name}!";
	}
}

```

With such class you should be able to run the following:

```php
echo "You are using the Home " . Home::version();
$home = new Home();
echo $home->enter('Bob');
```

And it will produce:
```
You are using the Home 1.0.0
Welcome Bob
```

Ok, now let's make methods filterable first:

```php
namespace city;

use filter\Filter;
use filter\behavior\Filterable;

class Home {

	use Filterable; // Only required for `enter` (i.e. instance methods)

	public static function version() {
		Filter::on(get_called_class(), __FUNCTION__, func_get_args(), function($chain) {
			return '1.0.0'; // Your inchanged code here
		});
	}

	public function enter($name) {
		Filter::on($this, __FUNCTION__, func_get_args(), function($chain, $name) {
			return "Welcome {$name}!"; // Your inchanged code here
		});
	}
}

```

So we end up doing a simple wrapping of the core implementation using a closure. Notice the use of `get_called_class()` or `$this` depends if you are in a static class or not. Don't forget the add all method parameters just after the mandatory `$chain` parameter in the closure definition. `$chain` will always be the first parameter and represents the chain of filters to apply.

Once the code rewrited, we are now ready to attach some filters to change the default behavior of methods.

```php
Filter::register('city.version', function($chain) { // Registering an aspect.
	$version = $chain->next();
	return "Version: {$version}";
});

Filter::register('city.civility', function($chain, $name) { // Registering another aspect.
	$name = "Mister {$name}";
	return $chain->next($name);
});


Filter::apply('city\Home', 'version' 'city.version'); // Applying `'city.version'` to the static method.

$home = new Home();
Filter::apply($home, 'enter', 'city.civility'); // Applying `'city.civility'` the the intance method.

echo "You are using the Home " . Home::version();
echo $home->enter('Bob');
```

And it will produce:
```
You are using the Home Version 1.0.0
Welcome Mister Bob
```

## FAQ

- **Is it possible to apply a filter for all instances ?** Yes, for such behavior, you need to set your filter using the class name string as context.

- **If sub class inherit from filters setted at a parent class level ?** Yes.

## API

### Making a method filterable

On a static method:
```php
class StaticClass {
	public static function($param1, $param2) {
		Filter::on(get_called_class(), __FUNCTION__, func_get_args(), function($chain, $param1, $param2) {
			// Method's logic here
		});
	}
}
```

On a dynamic method:
```php
use filter\behavior\Filterable;

class DynamicClass {
	use Filterable;

	public function($param1, $param2) {
		Filter::on($this, __FUNCTION__, func_get_args(), function($chain, $param1, $param2) {
			// Method's logic here
		});
	}
}
```

### Registering an aspect

```php
Filter::register('an_aspect_name', function($chain, $param1, $param2, ...) {
	// Method's logic here
	return $chain->next($param1, $param2, ...); // Process the chain if needed
});
```

### Appling a filter to a class or an instance

```php
Filter::apply($context, 'method_name', 'an_aspect_name');
```

### Detaching a filter from a class or an instance

Detach all filters associated to a method:
```php
Filter::detach($context, 'method_name');
```

Detach a named filter only:
```php
Filter::detach($context, 'method_name', 'an_aspect_name');
```

Detach a named filters only but for all class's method:
```php
Filter::detach($context, null, 'an_aspect_name');
```

### Checking if a closure is registred

```php
Filter::registred('an_aspect_name');
```

### Unregistering a closure

```php
Filter::unregister('an_aspect_name');
```

### Export/Restore the filtering system.

Getter:
```php
$registered = Filter::registered();
$filters = Filter::filters();
```

Setter:
```php
Filter::register($registered);
Filter::filters($filters);
```

### Clearing the registred closure & applied filters.

```php
Filter::reset();
```

Note: It also detaches all filters attached statically (i.e it doesn't affect filters on intance's methods).

### Enable/Disable the filter system globaly

```php
Filter::enable(); // Enable
Filter::enable(false); // Disable
```

Note: It doesn't detach any filters