# Usage

LiteWire DI resolves services by class or interface name. 

There are four public methods:

- `has()` checks whether a service can be resolved.
- `get()` returns a shared object.
- `make()` creates a fresh object.
- `set()` registers an object, class, factory, or named constructor parameters.

```php
$container->has( class-string $id ): bool;
$container->set( class-string $id, object|Closure|class-string|array<string, mixed> $service ): void;
$container->get( class-string $id );
$container->make( class-string $id, array $parameters = [] );
```

::: danger Important
Service IDs must be class or interface names. Plain strings like `logger` are not supported.
:::


## `has()` {#has}
Checks whether a service is registered, already resolved, or autowireable.

```php
$container->has( class-string $id ): bool;
```

Example:

```php
$container->has( Service::class ); // true if registered, resolved, or autowireable
$container->has( 'Unknown' );      // false
```
::: info
For an unregistered class, the full constructor graph must be valid.
::: 


## `get()` {#get}
Returns a shared service. The container creates it when needed and returns the same object on subsequent calls.

```php
$container->get( class-string $id );
```

### Autowiring

You can use `get()` without registering the class. The container creates it and automatically provides its constructor dependencies:

```php
class Logger {
	public function write( string $message ): void {
		error_log( $message );
	}
}

class Report_Service {
	public function __construct(
		private readonly Logger $logger,
	) {}

	public function generate(): void {
		$this->logger->write( 'Report generated.' );
	}
}

$service = $container->get( Report_Service::class );
$service->generate();
```

::: info
Neither `Report_Service` nor `Logger` needs registration. The container builds the graph automatically.
:::

### Services are shared

The container keeps the object after the first `get()` call. Later calls for the same service return that object again.

```php
$a = $container->get( Some_Service::class );
$b = $container->get( Some_Service::class );

$a === $b // true
```

### Scalar values

Work automatically only when they **have default values**:

```php
class Config {
	public function __construct(
		private readonly string $path = 'config.php',
	) {}
}

$config = $container->get( Config::class ); // no error
```

**Required scalar** values cannot be autowired. They throw `ContainerException`.

```php
class Config {
	public function __construct(
		private readonly string $path,
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



## `set()` {#set}
Registers a service definition.

```php
$container->set( class-string $id, object|Closure|class-string|array<string, mixed> $service ): void;
```

**`$id` parameter:**

The class or interface name used to identify the service.

::: danger
Service IDs must be class or interface names. Regular strings are not supported.

```php
$container->set( 'logger', File_Logger::class ); 
// Fatal: InvalidArgumentException
```
:::


**`$service` parameter:**

The value that defines the service or how to create it:

* Valid full class name - `MyClass::class`
* Existing object - `new MyClass()`
* Closure (factory) - `static fn () => new MyClass()`
* Array of named parameters (for an instantiable class) - `[ 'param_name' => $value ]`

Examples:

```php
$container->set( Logger_Interface::class, File_Logger::class ); // Class binding
$container->set( Cache::class, new Cache() ); // Existing object
$container->set( Mailer::class, static fn () => new Mailer() ); // Factory
$container->set( ReportWriter::class, [ 'outputDir' => __DIR__ . '/reports' ] ); // Constructor parameters
```

---

::: tip
Set a service before the first `get()`.
:::

::: tip
Most classes need no configuration: LiteWire DI creates them automatically when it can resolve their dependencies.

Use `set()` only when the container cannot determine a value by itself:

| Situation | What to register |
| --- | --- |
| An interface needs an implementation | [A class binding](#bind-an-interface) |
| A constructor needs a scalar value | [Named parameters](#provide-a-scalar-value) |
| Creation needs custom logic | [A closure factory](#register-a-factory) |
| The object already exists | [The object itself](#register-an-existing-object) |
:::

### Bind an interface

The container cannot guess which class implements an interface. Choose it once during application startup:

```php
interface Logger {
	public function log( string $message ): void;
}

class ErrorLogLogger implements Logger {
	public function log( string $message ): void {
		error_log( $message );
	}
}

$container->set( Logger::class, ErrorLogLogger::class );
```

Classes can now request `Logger` in their constructor, and the container will inject `ErrorLogLogger`.

### Provide a scalar value

Class dependencies are autowired, but values such as paths, URLs, and API keys must come from your application. Register them by constructor parameter name:

```php
class ReportWriter {
	public function __construct(
		private readonly Logger $logger,
		private readonly string $outputDir,
	) {}
}

$container->set( ReportWriter::class, [
	'outputDir' => __DIR__ . '/reports',
] );

$writer = $container->get( ReportWriter::class );
```

`Logger` is still autowired from the interface binding. Only the value the container cannot know is supplied explicitly. The configured result is shared by `get()`. Unknown parameter names throw `ContainerException` when the service is resolved.

Parameter arrays require an instantiable class as the service ID. To bind an interface whose implementation needs scalar values, use a factory.

### Register an existing object

If an object already exists, register it directly. Calls to `get()` return that object:

```php
$container->set( Logger::class, $logger );
$service = $container->get( ReportWriter::class );
```

Existing objects cannot be used with [`make()`](#existing-objects-cannot-be-used-with-make).

### Register a factory

#### Interface implementation

Use a closure when selecting an implementation also requires scalar values or custom setup. This is an alternative to the `ErrorLogLogger` binding above; choose one during startup before resolving `ReportWriter`:

```php
class FileLogger implements Logger {
	public function __construct(
		private readonly string $file,
	) {}

	public function log( string $message ): void {
		file_put_contents( $this->file, $message . PHP_EOL, FILE_APPEND );
	}
}

$container->set( Logger::class, static function () {
	return new FileLogger( __DIR__ . '/logs/application.log' );
} );
```

::: warning
The factory must return an object.
:::

#### Dependencies are autowired

Factory parameters are autowired for both `get()` and `make()`:

```php
$container->set( Mailer::class, static function ( Logger $logger ) {
	return new Mailer( $logger );
} );

$sharedMailer = $container->get( Mailer::class );
$freshMailer = $container->make( Mailer::class );
```

#### Container access

Type-hint `Container` in a factory to receive the container itself:

```php
$container->set( Plugin::class, static function ( Container $container ) {
	return new Plugin( $container->get( Config::class ) );
} );
```

#### Runtime parameters

Pass named parameters to `make()` to supply values to a factory. The factory can process them before passing them to the constructor:

```php
$container->set( Logger::class, static function ( string $name ) {
	$file = __DIR__ . '/logs/' . basename( $name ) . '.log';
	return new FileLogger( $file );
} );

$logger = $container->make( Logger::class, [
	'name' => 'application',
] );
```

The parameter name must match the factory parameter (`name`), not the constructor parameter (`file`). This factory requires `name`, so `get( Logger::class )` cannot call it without a default value.

#### `make()` in a factory

You can also use `make()` inside a factory to provide a scalar value while autowiring the remaining dependencies:

```php
interface Report {}

class ReportService implements Report {
	public function __construct(
		private readonly string $outputDir,
		private readonly Logger $logger,
	) {}
}

$container->set( Report::class, static function ( Container $container ) {
	return $container->make( ReportService::class, [
		'outputDir' => __DIR__ . '/reports',
	] );
} );

$report = $container->get( Report::class );
```

The factory is registered for `Report`, while `make()` creates `ReportService`. Calling `make()` with the same ID as the factory would cause a circular dependency. For a concrete class without an interface binding, [register the scalar value directly](#provide-a-scalar-value).


### Replace a service

Calling `set()` again replaces the service used by future requests. It does not change objects that were already created:

```php
class Logger {}

class Report {
	public function __construct( 
		public readonly Logger $logger 
	) {}
}

$oldLogger = new Logger();
$container->set( Logger::class, $oldLogger );
$report = $container->get( Report::class );

$newLogger = new Logger();
$container->set( Logger::class, $newLogger );

$container->get( Logger::class ) === $oldLogger; // false
$container->get( Logger::class ) === $newLogger; // true
$report->logger === $oldLogger;                  // true
$container->get( Report::class ) === $report;    // true: get() returns the same report
$container->make( Report::class )->logger === $newLogger; // true: a new report
```

The existing `$report` still has the logger it received when it was created. A new report receives the replacement logger.

---

::: info
See [App config options](/guide/app-config-options) for ways to organize and reuse application settings.
:::

## `make()` {#make}
Creates a fresh object from a registered definition or class name.

Unlike `get()`, `make()` does not save the object in the container for later reuse.

```php
$container->make( class-string $id, array $parameters = [] );
```


### New instances
```php
$a = $container->make( Some_Service::class );
$b = $container->make( Some_Service::class );

var_dump( $a === $b ); // false
```

This is useful for stateful objects, DTOs, handlers, commands, forms, and other short-lived objects.


### Runtime parameters
Pass values to `make()` as an array where each key is a constructor or factory parameter name. The container uses these values and autowires any remaining class dependencies.

::: warning
Unknown parameter names throw `ContainerException`.
:::

This makes `make()` useful for objects that mix services with runtime values.

```php
class Mailer {
	public function __construct(
		private readonly Logger $logger,
		private readonly string $from,
	) {}
}

$mailer = $container->make( Mailer::class, [
	'from' => 'admin@example.com',
] );
```

If parameters were registered with `set()`, you can override them in `make()`:

```php
$container->set( Mailer::class, [
	'from' => 'default@example.com',
] );

$mailer = $container->make( Mailer::class, [
	'from' => 'override@example.com',
] );
```


### Existing objects cannot be used with `make()`
::: warning
Definitions registered as existing object instances cannot be used with `make()`. Use `get()` to retrieve those instances.
:::

```php
$logger = new Logger();

$container->set( Logger::class, $logger );

$same_logger = $container->get( Logger::class ); // OK.
$new_logger = $container->make( Logger::class ); // Throws ContainerException.
```


### Dependencies are shared
Only the requested root object is created anew. Class dependencies are resolved with `get()`, so they are shared and reused by subsequent calls.

```php
class ReportController {
	public function __construct(
		public readonly Logger $logger,
	) {}
}

$first = $container->make( ReportController::class );
$second = $container->make( ReportController::class );

var_dump( $first === $second ); // false
var_dump( $first->logger === $second->logger ); // true
```


### How `make()` uses registered definitions
If you registered [a class](#bind-an-interface) or [constructor parameters](#provide-a-scalar-value) with `set()`, `make()` uses them to create an object. If you registered [a factory](#register-a-factory), `make()` calls it each time.
