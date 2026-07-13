# LiteWire DI Container

A tiny single-file autowiring DI container for PHP and WordPress.


Compatibility
-------------

PHP 7.4 and PHP 8.0-8.5 are tested in CI.


Design goals
------------
LiteWire DI is built for small projects, plugins, themes, and libraries where Symfony DI or PHP-DI would be too much.

It is not a replacement for large framework containers. It is for simple autowiring without config files, compiled cache, service providers, tags, scopes, scalar storage, or framework integration.

The API follows the familiar `get()` / `has()` shape:

```php
$container->get( Service::class );
$container->has( Service::class );
```

That keeps migration to a larger container straightforward.

It does not implement `Psr\Container\ContainerInterface` and does not require `psr/container`.


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

Tradeoffs:

- Configuration goes through factories, runtime parameters, or config objects. There is no separate config array.
- Invokable objects can be wrapped in closures, but are not factories automatically.
- Configuration is normal PHP code. There are no attributes or DSL.


Usage Guide
-----------
Four public methods:

- `has()` checks if a service can be resolved.
- `set()` registers an object, class, or factory.
- `get()` returns a shared object.
- `make()` creates a fresh object.

API:
```php
$container->has( class-string $id ): bool;
$container->set( class-string $id, object|Closure|class-string $service ): void;
$container->get( class-string $id );
$container->make( class-string $id, array $parameters = [] );
```

Service IDs must be class or interface names. Plain strings like `logger` are not supported.


### Usage Example

`Logger` is created automatically from the constructor type.

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



has()
-----
Checks whether a service is registered, already resolved, or autowireable.

For an unregistered class, the full constructor graph must be valid.

Usage:
```php
$container->has( Service::class ); // true if registered, resolved, or autowireable
$container->has( 'Unknown' );      // false
```


get()
-----
Returns a shared service instance.

If it was already created, the same object is returned.

### get() - Autowiring

`get()` can create an unregistered class from constructor types:

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

Neither `Report_Service` nor `Logger` needs registration. The container builds the graph automatically.


### get() - Shared services

`get()` stores the resolved service. The next call returns the same instance.

```php
$a = $container->get( Some_Service::class );
$b = $container->get( Some_Service::class );

var_dump( $a === $b ); // true
```

### get() - Scalar values

Required scalar values cannot be autowired. They throw `ContainerException`.

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

Service IDs must be class or interface names. Regular strings are not supported:

```php
$container->set( Logger_Interface::class, File_Logger::class ); // valid
$container->set( 'logger', File_Logger::class );                // InvalidArgumentException
```

Accepted values:

* existing object - `new MyClass()`
* existing class name - `MyClass::class`
* closure factory - `static fn () => new MyClass()`

> [!IMPORTANT]
> Configure the container before the first `get()`. Replacing a definition removes the stored instance for that ID, but already-created services are not rebuilt.


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

Type-hint `Container` in a factory parameter to receive the container:

```php
$container->set( Plugin::class, static function ( Container $container ) {
	return new Plugin( $container->get( Config::class ) );
} );

$plugin = $container->get( Plugin::class );
```


make()
------
Creates a fresh object from a registered definition or class name.

Unlike `get()`, it does not store the object.


### make() - New instances
`make()` creates a new object without saving it.

```php
$a = $container->make( Some_Service::class );
$b = $container->make( Some_Service::class );

var_dump( $a === $b ); // false
```

This is useful for stateful objects, DTOs, handlers, commands, forms, and other short-lived objects.


### make() - Runtime parameters
The second argument of `make()` is an array keyed by constructor parameter name. Provided values go straight to the constructor. Missing class dependencies are autowired.

Unknown parameter names throw `ContainerException`.

This makes `make()` useful for objects that mix services with runtime values. In tests, the factory call can be replaced with a mock.

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


### make() - Existing object definitions
Definitions registered as existing object instances cannot be used with `make()`. Use `get()` to retrieve those instances.

```php
$logger = new Logger();

$container->set( Logger::class, $logger );

$same_logger = $container->get( Logger::class ); // OK.
$new_logger = $container->make( Logger::class ); // Throws ContainerException.
```


### make() - Shared dependencies
Only the requested root object is created anew. Class dependencies are resolved with `get()`, so they are shared and reused by subsequent calls.

```php
class ReportController {
	public function __construct( public Logger $logger ) {}
}

$first = $container->make( ReportController::class );
$second = $container->make( ReportController::class );

var_dump( $first === $second ); // false
var_dump( $first->logger === $second->logger ); // true
```


### make() - Registered definitions
Class-string definitions are instantiated again, and closure factories are invoked on every call.

```php
$container->set( Mailer::class, static fn () => new Mailer() );

var_dump( $container->make( Mailer::class ) === $container->make( Mailer::class ) ); // false
var_dump( $container->get( Mailer::class ) === $container->get( Mailer::class ) ); // true
```



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
LiteWire DI does not include:

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

Required scalar constructor parameters must be provided by your code.

These features would make the API larger. LiteWire DI keeps one strict object model in one small PHP file.

Full PSR-11 support would require `psr/container`. LiteWire DI keeps the API style without the dependency. An optional adapter may be added later.
