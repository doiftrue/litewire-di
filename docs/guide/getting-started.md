# Getting started

LiteWire DI is a tiny single-file autowiring container for PHP applications, libraries, WordPress plugins, and themes. It has no runtime dependencies and does not implement `Psr\Container\ContainerInterface` or require `psr/container`.

## Requirements

LiteWire DI supports PHP 7.4 and PHP 8.0–8.5.

## Installation

Install it with Composer:

```bash
composer require doiftrue/litewire-di
```

Alternatively, copy [`Container.php`](https://github.com/doiftrue/litewire-di/blob/main/Container.php) into your project.

::: warning Use your own namespace
When copying the file, change its `Kama\LiteWireDI` namespace to one owned by your project so it cannot conflict with another copied version.
:::

## Resolve your first service

```php
use Kama\LiteWireDI\Container;

final class Logger {
	public function log( string $message ): void {
		error_log( $message );
	}
}

final class ReportService {
	public function __construct(
		private readonly Logger $logger,
	) {}

	public function generate(): void {
		$this->logger->log( 'Report generated.' );
	}
}

$container = new Container();
$container->get( ReportService::class )->generate();
```

Neither class is registered. `get()` reflects `ReportService`, sees its `Logger` dependency, creates it, and stores the completed `ReportService` as a shared instance.

## Features

- Keep the whole container in a single PHP file.
- Use no external dependencies.
- Register existing objects, classes, closure factories, and configured constructor parameters with `set()`.
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

## Tradeoffs

- Configuration goes through parameters attached to concrete class definitions, factories, runtime parameters, or config objects. There is no standalone scalar storage.
- Invokable objects can be wrapped in closures, but are not factories automatically.
- Configuration is normal PHP code. There are no attributes or DSL (domain-specific language).

---

::: info Next step
Continue with [using the container](/guide/using-the-container) to learn when to use each public method.
:::
