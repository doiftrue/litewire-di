# Configuration and factories

LiteWire DI stores objects, not standalone scalar entries. Keep configuration in application code, then supply it through a typed object, named parameters, or a factory.

## Register a configuration object

This is usually the clearest approach when several services share related values.

```php
final class AppConfig {
	public $cacheDirectory;

	public function __construct( string $cacheDirectory ) {
		$this->cacheDirectory = $cacheDirectory;
	}
}

$container->set( AppConfig::class, new AppConfig( __DIR__ . '/var/cache' ) );
```

Any service with an `AppConfig` constructor argument can now be autowired.

## Configure one concrete class

Pass an associative array whose keys exactly match constructor parameter names. This form only works for an instantiable class, not an interface.

```php
$container->set( ReportWriter::class, [
	'outputPath' => __DIR__ . '/reports',
] );
```

Missing object dependencies are still autowired. `make( ReportWriter::class, [ 'outputPath' => '/tmp' ] )` uses `/tmp` for that call without changing the configured default.

## Bind an interface or use custom construction

An interface needs an implementation choice. A closure factory is appropriate when construction also requires scalar values or custom logic.

```php
$container->set( LoggerInterface::class, static function ( AppConfig $config ) {
	return new FileLogger( $config->cacheDirectory . '/application.log' );
} );
```

Factory parameters are autowired just like constructor parameters, and the factory must return an object.

::: warning
Configure the container before calling `get()`. Changing a definition later does not update already-created objects that depend on the previous one.
:::

## Choose a configuration pattern

All examples in this guide use PHP 7.4-compatible syntax.

### One typed configuration object

Use one object when related values are used in several places. It keeps service constructors typed and avoids calls to `getenv()` or globals throughout the application.

```php
final class DatabaseConfig {
	public $host;
	public $port;

	public function __construct( string $host, int $port ) {
		$this->host = $host;
		$this->port = $port;
	}
}

$container->set( DatabaseConfig::class, new DatabaseConfig( 'localhost', 3306 ) );
```

### Focused configuration objects

When one configuration object becomes a bag of unrelated settings, split it by responsibility: for example, `DatabaseConfig`, `MailConfig`, and `CacheConfig`. A service should request only the configuration it uses.

### Named parameters for one concrete service

Use this concise form when values belong only to one instantiable class. Parameter names are coupled to that constructor, and values are checked at runtime.

```php
$container->set( Connection::class, [
	'host' => 'localhost',
	'port' => 3306,
] );
```

### Map a loaded array into an object

If an application already returns an array from `config.php`, validate or transform it in bootstrap code and then construct a typed configuration object. This is safer for services than passing the array throughout the application.

```php
$values = require __DIR__ . '/config.php';
$container->set( DatabaseConfig::class, new DatabaseConfig(
	$values['db_host'],
	$values['db_port']
) );
```

### Use a factory when construction has logic

Factories are best for selecting an interface implementation, building a third-party object, or combining scalar values with custom construction. Do not use an invokable object directly: wrap it in a closure, because only closures are treated as factories.
