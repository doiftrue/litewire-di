# Mini Autowire Container

> ![INFO]
> Inspired by [Simple DIC](https://github.com/renakdup/simple-dic)
> 
> Наш контейнер отличается:
> 1.
> set() принимает только объекты или class-string (не примитивы)
> 2.
> get() — singleton, всегда кеширует
> 3.
> make() — всегда новый экземпляр, поддерживает runtime-параметры
> 4.
> Factory в make() — автовайринг параметров closure (не просто $container)
> 5.
> Factory в get() — closure получает $this (контейнер)


A tiny single-file autowire DI container for PHP and WordPress applications.

It is designed for small projects, plugins, themes, and libraries where a full-featured container like Symfony DI or PHP-DI would be too much.

## Features

* Single PHP file
* No dependencies
* Autowiring by constructor type hints
* Shared services via `get()`
* New instances via `make()`
* Factory closures
* Runtime constructor parameters
* Object-only resolved services
* Reflection static (runtime) cache

## Installation

Copy `Container.php` into your project and load it manually or through your autoloader.

```php
use Kama\MiniContainer\Container;
```

## Basic usage

```php
use Kama\MiniContainer\Container;

$container = new Container();

$service = $container->get( Some_Service::class );
```

If `Some_Service` has constructor dependencies with class type hints, they will be resolved automatically.

```php
final class Some_Service {

	public function __construct(
		private Logger $logger,
		private Repository $repository
	) {}
}

$service = $container->get( Some_Service::class );
```

## Register an existing object

```php
$container->set( Logger::class, new Logger() );

$logger = $container->get( Logger::class );
```

## Register an interface implementation

```php
$container->set( Logger_Interface::class, File_Logger::class );

$logger = $container->get( Logger_Interface::class );
```

## Register a factory

```php
$container->set( Client::class, static function (): Client {
	return new Client( 'https://example.com' );
} );

$client = $container->get( Client::class );
```

Factories must return an object.

## Shared services with get()

`get()` resolves a service and stores it in the container. The next call returns the same instance.

```php
$a = $container->get( Some_Service::class );
$b = $container->get( Some_Service::class );

var_dump( $a === $b ); // true
```

## New instances with make()

`make()` creates a new object and does not save it in the container.

```php
$a = $container->make( Some_Service::class );
$b = $container->make( Some_Service::class );

var_dump( $a === $b ); // false
```

This is useful for stateful objects, DTOs, handlers, commands, forms, and other short-lived objects.

## Runtime parameters

Named runtime parameters can be passed to `make()`.

```php
final class Mailer {

	public function __construct(
		private Logger $logger,
		private string $from
	) {}
}

$mailer = $container->make( Mailer::class, [
	'from' => 'admin@example.com',
] );
```

Class dependencies are still autowired automatically. Scalar values must be passed manually or have default values.

## Default values

Optional scalar constructor parameters are resolved from their default values.

```php
final class Api_Client {

	public function __construct(
		private Logger $logger,
		private int $timeout = 10
	) {}
}

$client = $container->get( Api_Client::class );
```

## Container API

```php
$container->set( string $id, object|Closure|string $service ): void;

$container->has( string $id ): bool;

$container->get( string $id );

$container->make( string $id, array $parameters = [] );
```

### `set()`

Registers a service definition.

The definition can be:

* existing object
* class name
* closure factory

### `has()`

Checks whether the service was registered or already resolved.

### `get()`

Returns a shared service instance. If the service was not created yet, it will be resolved and cached.

### `make()`

Creates a fresh object from a definition or class name. The result is not cached.

## WordPress example

```php
use Kama\MiniContainer\Container;

$container = new Container();

$container->set( Plugin::class, Plugin::class );

add_action( 'plugins_loaded', static function () use ( $container ) {
	$container->get( Plugin::class )->init();
} );
```

## Design goals

This container is intentionally small.

It is not trying to replace Symfony DI, PHP-DI, Laravel Container, or other full-featured dependency injection containers. It is useful when you need simple autowiring without configuration files, compiled cache, service providers, tags, scopes, scalar parameter storage, or framework integration.

The main idea is to keep application code close to common DI container concepts:

```php
$container->get( Service::class );
$container->has( Service::class );
```

For larger projects, this makes migration to a bigger container easier.

## Limitations

* No PSR-11 interface dependency included
* No compiled container
* No service providers
* No scopes
* No tags
* No scalar parameter storage
* No circular dependency detection
* No union/intersection type resolving
* Required scalar constructor parameters must be provided manually

## License

MIT
