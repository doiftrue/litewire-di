# LiteWire DI Container

A tiny single-file autowire DI container for PHP and WordPress applications.


Compatibility
-------------

LiteWire DI supports PHP 7.4 and every PHP 8 minor release from 8.0 through 8.5. Each supported version is tested by CI. Future PHP versions are added after compatibility has been verified.


Design goals
------------
This container is intentionally small. It is designed for small projects, plugins, themes, and libraries where a full-featured container like Symfony DI or PHP-DI would be too much.

It is not trying to replace Symfony DI, PHP-DI, Laravel Container, or other full-featured dependency injection containers. It is useful when you need simple autowiring without configuration files, compiled cache, service providers, tags, scopes, scalar parameter storage, or framework integration.

The main idea is to provide a PSR-11-style API built around `get()` and `has()`:

```php
$container->get( Service::class );
$container->has( Service::class );
```

For larger projects, this makes migration to a bigger container easier.

The container does not implement `Psr\Container\ContainerInterface` and does not depend on `psr/container`.


Features
--------

- Keep the whole container in a single PHP file.
- Use no external dependencies.
- Register existing objects, classes, and closure factories with `set()`.
- Autowire registered and unregistered classes.
- Return shared service instances with `get()`.
- Create a new instance every time with `make()`.
- Pass named runtime parameters to `make()`.
- Check whether classes and interfaces can be resolved with `has()`.
- Use an object-first design with class and interface names as service IDs.
- Use default values for scalar constructor parameters.
- Use the modern Reflection API on PHP 8.
- Inject the container itself as a dependency.
- Cache Reflection data inside each container instance.
- Detect circular dependencies and show the full resolution chain.

Partially supported features:

- Services can receive configuration through factories, runtime parameters, or a registered configuration object, but the container does not have a separate configuration array.
- Invokable objects can be wrapped in a closure, but objects with `__invoke()` are not treated as factories automatically.
- The container uses normal PHP code for configuration. It does not provide attributes or a special configuration language.


Usage Guide
-----------
LiteWire DI has four public methods:

- `has()` checks if a service can be loaded.
- `set()` tells the container how to create an object.
- `get()` returns a shared object and creates it if it does not exist yet.
- `make()` creates a new object.

API:
```php
$container->has( class-string $id ): bool;
$container->set( class-string $id, object|Closure|class-string $service ): void;
$container->get( class-string $id );
$container->make( class-string $id, array $parameters = [] );
```

All service IDs must be real class or interface names. Plain names such as `logger` are not supported.


Basic usage
-----------
`Logger` will be created automatically because it is declared as a constructor dependency.

```php
class Logger {
	public function log( string $message ): void {
		error_log( $message );
	}
}

class Service {
	public function __construct(
		private Logger $logger
	) {}

	public function run(): void {
		$this->logger->log( 'Service started.' );
	}
}

$container = new Container();
$service = $container->get( Service::class );
$service->run();
```


WordPress example:

```php
$container = new Container();

// Register factory for Plugin class
$container->set( Plugin::class, function () {
	return new Plugin( __FILE__ );
} );

add_action( 'plugins_loaded', function () use ( $container ) {
	$container->get( Plugin::class )->init();
} );
```


has()
-----
Checks whether the service was registered, already resolved, or can be autowired.

For an unregistered class, its constructor and complete dependency graph must be valid.

Usage:
```php
$container->has( Service::class ); // true if registered, resolved, or autowireable
$container->has( 'Unknown' );      // false
```


get()
-----
Gets a shared service instance.

If the service was already created, the same object is returned.

### get() - Autowiring

`get()` can create an unregistered class by resolving the class dependencies declared in its constructor:

```php
class Logger {
	public function write( string $message ): void {
		error_log( $message );
	}
}

class Report_Service {
	private $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function generate(): void {
		$this->logger->write( 'Report generated.' );
	}
}

$service = $container->get( Report_Service::class );
$service->generate();
```

Neither `Report_Service` nor `Logger` needs to be registered: the container inspects both constructors and builds the complete dependency graph automatically.


### get() - Shared services

`get()` resolves a service and stores it in the container. The next call returns the same instance.

```php
$a = $container->get( Some_Service::class );
$b = $container->get( Some_Service::class );

var_dump( $a === $b ); // true
```

### get() - Scalar values

Required scalar values cannot be resolved automatically. `ContainerException` will be thrown.

```php
final class Config {
	public function __construct(
		private string $path
	) {}
}

$container->get( Config::class ); // ContainerException
```

Use `make()` with named parameters:
```php
$config = $container->make( Config::class, [
	'path' => __DIR__ . '/config.php',
] );
```

Optional scalar values are supported:
```php
final class Config {
	public function __construct(
		private string $path = 'config.php'
	) {}
}

$config = $container->get( Config::class ); // no error
```


set()
-----
Registers a service definition.

Service IDs must be existing class/interface names. Regular strings are not supported:

```php
$container->set( Logger_Interface::class, File_Logger::class ); // valid
$container->set( 'logger', File_Logger::class );                // InvalidArgumentException
```

Accepted values:

* existing object - `new MyClass()`
* existing class name - `MyClass::class`
* closure factory - `static fn () => new MyClass()`

> [!IMPORTANT]
> Configure the container before the first call to `get()`. Replacing a definition with `set()` removes the stored instance for that ID, but does not rebuild other shared services that were already created with the previous instance as a dependency.


### set() - Register an existing object
```php
$container->set( Logger::class, $logger );
$service = $container->get( Service::class );
```


### set() - Register an interface implementation
```php
$container->set( Logger_Interface::class, File_Logger::class );
$logger = $container->get( Logger_Interface::class );
```


### set() - Register a factory
Factories must return an object.

```php
$container->set( Client::class, static function () {
	return new Client( 'https://example.com' );
} );

$client = $container->get( Client::class );
```

### set() - Factory autowiring
Factory parameters are autowired for both `get()` and `make()`:

```php
$container->set( Mailer::class, static function ( Logger $logger ) {
	return new Mailer( $logger );
} );

$shared_mailer = $container->get( Mailer::class );
$fresh_mailer = $container->make( Mailer::class );
```

Type-hint `Container` in factory parameters to receive the container:

```php
$container->set( Plugin::class, static function ( Container $container ) {
	return new Plugin( $container->get( Config::class ) );
} );

$plugin = $container->get( Plugin::class );
```


make()
------
Creates a fresh object each time from a registered definition or class name.

Unlike `get()`, it does not store the created object in the container.

> [!NOTE]
> Definitions registered as existing object instances cannot be used with `make()` - use `get()` to retrieve those instances.

> [!NOTE]
> Only the requested root object is created anew. Missing class dependencies are resolved through `get()`, so those dependencies are shared and reused by subsequent calls.

> [!NOTE]
> Class-string definitions are instantiated again, and closure factories are invoked on every call.

> [!NOTE]
> Factories must return an object but are responsible for whether that object is a new instance.


### make() - New instances
`make()` creates a new object and does not save it in the container.

```php
$a = $container->make( Some_Service::class );
$b = $container->make( Some_Service::class );

var_dump( $a === $b ); // false
```

This is useful for stateful objects, DTOs, handlers, commands, forms, and other short-lived objects.


### make() - Runtime parameters
The second argument of `make()` is an array of constructor parameters, keyed by parameter name. Values provided in this array are passed directly to the constructor. Any missing class dependencies are resolved automatically by the container.

If the array contains a parameter name that does not exist in the constructor, `make()` throws a `ContainerException`.

This allows `make()` to be used as a factory for objects that combine autowired services with runtime values. The factory call can then be replaced with a mock in tests.

```php
class Mailer {
	public function __construct(
		private Logger $logger,
		private string $from
	) {}
}

$mailer = $container->make( Mailer::class, [
	'from' => 'admin@example.com',
] );
```


Performance
-----------

The detailed benchmark report is available in the [Benchmarks](#benchmarks) section.


Comparison with other containers
--------------------------------

| Container              | Deps |     PSR-11 | Autowiring |           Shared services |   New instance method | Scalars | Config |
|------------------------|-----:|-----------:|-----------:|--------------------------:|----------------------:|--------:|-------:|
| LiteWire DI            |   no |      style |        yes |                   `get()` |              `make()` | runtime |     no |
| PHP-DI                 |  yes |        yes |        yes |                   `get()` |              `make()` |     yes |    yes |
| Symfony DI             |  yes |        yes |        yes |                   `get()` |    factories/services |     yes |    yes |
| Laravel Container      |  yes | partly/yes |        yes | `singleton(), instance()` |              `make()` |     yes |    yes |
| Laminas ServiceManager |  yes |        yes |        yes |           shared services |             `build()` |     yes |    yes |
| League Container       |  yes |        yes |        yes |             `addShared()` | definitions/factories |     yes |    yes |
| Yii DI Container       |  yes |         no |        yes |          `setSingleton()` |               `get()` |     yes |    yes |
| Pimple                 |  yes |         no |         no |          default behavior |           `factory()` |     yes |    yes |
| Nette DI               |  yes | no/adapter |        yes |        generated services |   generated factories |     yes |    yes |

Best for:

* `LiteWire DI` - simple PHP apps, WP plugins
* `PHP-DI` - medium and large apps
* `Symfony DI` - Symfony apps, compiled container
* `Laravel Container` - Laravel apps
* `Laminas ServiceManager` - Laminas/Mezzio apps
* `Pimple` - small explicit service containers
* `League Container` - framework-agnostic DI
* `Yii DI Container` - Yii apps
* `Nette DI` - Nette apps


Limitations
-----------
LiteWire DI intentionally does not include:

- A compiled container.
- Complex configuration files.
- Attributes or a special configuration language (DSL).
- A debug mode.
- Arbitrary string service IDs.
- Invokable objects used directly as factories.
- Service definitions passed through the container constructor.
- Service providers.
- Scopes.
- Tags.
- Scalar parameter storage.
- Union or intersection type resolution.
- Variadic parameter resolution.

Required scalar constructor parameters must be provided manually.

These features would make the public API larger and less focused. The main advantage of LiteWire DI is its strict object model in one small PHP file.

Full PSR-11 support is also a tradeoff because it requires a dependency on `psr/container`. LiteWire DI keeps a PSR-11-style API instead. A separate optional adapter may be added in the future.
