# App config comparison

These examples show how LiteWire DI, PHP-DI, Symfony DI, and Laravel handle application settings. For approaches focused on LiteWire DI, see [App config options](/guide/app-config-options).

All examples use PHP 8.2 syntax.

**The examples below use this service:**

```php
class SomeService {
	public function __construct(
		private readonly string $db_host,
		private readonly int $db_port,
		private readonly string $db_name,
		private readonly string $api_url,
		private readonly int $api_timeout,
		private readonly string $cache_dir,
		private readonly bool $debug,
	) {}
}
```


LiteWire DI
-----------
LiteWire DI stores objects rather than standalone scalar entries. It can pass settings through a registered configuration object, named parameters attached to a concrete class, a factory, or runtime parameters. 

See [App config options](/guide/app-config-options) for complete examples.

PHP-DI
------
PHP-DI can store values under string IDs such as `database.host` and `app.debug`. It can pass those values directly to the service constructor.

The PHP-DI definition file contains both the values and instructions that connect each value to a constructor argument:

file: `config.php`
```php
use function DI\autowire;
use function DI\get;

return [
	'database.host' => 'localhost',
	'database.port' => 3306,
	'database.name' => 'my_application',
	'api.url' => 'https://api.example.com',
	'api.timeout' => 10,
	'cache.directory' => __DIR__ . '/var/cache',
	'app.debug' => true,

	SomeService::class => autowire()
		->constructorParameter( 'db_host', get( 'database.host' ) )
		->constructorParameter( 'db_port', get( 'database.port' ) )
		->constructorParameter( 'db_name', get( 'database.name' ) )
		->constructorParameter( 'api_url', get( 'api.url' ) )
		->constructorParameter( 'api_timeout', get( 'api.timeout' ) )
		->constructorParameter( 'cache_dir', get( 'cache.directory' ) )
		->constructorParameter( 'debug', get( 'app.debug' ) ),
];
```

file: `bootstrap.php`
```php
$builder = new DI\ContainerBuilder();
$builder->addDefinitions( __DIR__ . '/config.php' );
$container = $builder->build();

$some_service = $container->get( SomeService::class );
```

PHP-DI resolves every `get()` reference and passes the corresponding value to `SomeService`. The container owns both the scalar values and the rules for injecting them.


Symfony DI
----------
Symfony stores scalar values as container parameters. A service can reference those parameters in `services.yaml`:

file: `config/services.yaml`
```yaml
parameters:
    database.host: 'localhost'
    database.port: 3306
    database.name: 'my_application'
    api.url: 'https://api.example.com'
    api.timeout: 10
    cache.directory: '%kernel.project_dir%/var/cache'
    app.debug: true

services:
    SomeService:
        public: true
        arguments:
            $db_host: '%database.host%'
            $db_port: '%database.port%'
            $db_name: '%database.name%'
            $api_url: '%api.url%'
            $api_timeout: '%api.timeout%'
            $cache_dir: '%cache.directory%'
            $debug: '%app.debug%'
```

file: `bootstrap.php`
```php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container = new ContainerBuilder();
$container->setParameter( 'kernel.project_dir', __DIR__ );

$loader = new YamlFileLoader(
	$container,
	new FileLocator( __DIR__ . '/config' )
);
$loader->load( 'services.yaml' );

$container->compile();

$some_service = $container->get( SomeService::class );
```

::: info
The bootstrap code creates the container, loads `services.yaml`, and compiles all definitions.

Symfony resolves every `%parameter%` reference while compiling the container.

`public: true` is used only so this small example can call `get()` directly. Application services are normally private and injected into other services.
:::


Laravel Container
-----------------
Laravel stores application settings in files inside the `config` directory:

file: `config/some_service.php`
```php
return [
	'db_host' => 'localhost',
	'db_port' => 3306,
	'db_name' => 'my_application',
	'api_url' => 'https://api.example.com',
	'api_timeout' => 10,
	'cache_dir' => storage_path( 'framework/cache' ),
	'debug' => true,
];
```

A service provider uses contextual binding to connect each constructor argument to a configuration value:

file: `app/Providers/SomeServiceProvider.php`
```php
use Illuminate\Support\ServiceProvider;

class SomeServiceProvider extends ServiceProvider {
	public function register(): void {
		$this->app->when( SomeService::class )
			->needs( '$db_host' )
			->giveConfig( 'some_service.db_host' );

		$this->app->when( SomeService::class )
			->needs( '$db_port' )
			->giveConfig( 'some_service.db_port' );

		$this->app->when( SomeService::class )
			->needs( '$db_name' )
			->giveConfig( 'some_service.db_name' );

		$this->app->when( SomeService::class )
			->needs( '$api_url' )
			->giveConfig( 'some_service.api_url' );

		$this->app->when( SomeService::class )
			->needs( '$api_timeout' )
			->giveConfig( 'some_service.api_timeout' );

		$this->app->when( SomeService::class )
			->needs( '$cache_dir' )
			->giveConfig( 'some_service.cache_dir' );

		$this->app->when( SomeService::class )
			->needs( '$debug' )
			->giveConfig( 'some_service.debug' );
	}
}
```

The bootstrap code creates the application container, loads the configuration, and registers the provider:

file: `bootstrap.php`
```php
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;

$app = new Application( __DIR__ );

$app->instance( 'config', new Repository( [
	'some_service' => require __DIR__ . '/config/some_service.php',
] ) );

$app->register( SomeServiceProvider::class );

$some_service = $app->make( SomeService::class );
```

`$app->register()` creates the provider and calls its `register()` method. Laravel then reads the configured values and injects them when `$app->make()` resolves `SomeService`.



Are these approaches equivalent?
---------------------

They create the same `SomeService` with the same settings. PHP-DI can store individual values and inject them by reference. LiteWire DI either stores typed configuration objects or attaches literal named values directly to one concrete class definition.

The container features are also not equivalent. PHP-DI stores scalar entries and can reference, combine, and override definitions. LiteWire DI does not expose scalar entries: its configured parameter values belong only to their class definition. Loading, combining, or validating the original values remains application code.

For a small application, one `AppConfig` object is often enough. If it becomes too large, split it into focused objects such as `DatabaseConfig`, `ApiConfig`, and `CacheConfig` and register each object in the same way.
