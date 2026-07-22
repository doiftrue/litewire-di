# Getting started

LiteWire DI is a tiny single-file autowiring container for PHP applications, libraries, WordPress plugins, and themes. It has no runtime dependencies and does not implement `Psr\Container\ContainerInterface` or require `psr/container`.

## Requirements

LiteWire DI supports PHP 7.4 and PHP 8.0–8.5. Install it with Composer:

```bash
composer require doiftrue/litewire-di
```

Alternatively, copy [`Container.php`](https://github.com/doiftrue/litewire-di/blob/main/Container.php) into your project. Change its `Kama\LiteWireDI` namespace to one owned by your project, so it cannot conflict with another copied version.

## Resolve your first service

```php
use Kama\LiteWireDI\Container;

final class Logger {
	public function log( string $message ): void {
		error_log( $message );
	}
}

final class ReportService {
	private $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function generate(): void {
		$this->logger->log( 'Report generated.' );
	}
}

$container = new Container();
$container->get( ReportService::class )->generate();
```

Neither class is registered. `get()` reflects `ReportService`, sees its `Logger` dependency, creates it, and stores the completed `ReportService` as a shared instance.

Continue with [using the container](/guide/using-the-container) to learn when to use each public method.
