# Prefixing namespaces

When multiple WordPress plugins use LiteWire DI, each can ship its own version. A project-specific namespace keeps those copies from conflicting.

This example uses [Strauss](https://github.com/BrianHenryIE/strauss), a tool that copies Composer packages and prefixes their namespaces. Run these commands in your plugin directory.

## 1. Configure the prefix

Create a `composer.json` file, or add the `extra` section to your existing one:

```json
{
  "name": "example/my-plugin",
  "extra": {
    "strauss": {
      "namespace_prefix": "MyCompany\\MyPlugin\\Vendor\\",
      "packages": ["doiftrue/litewire-di"]
    }
  }
}
```

Replace `MyCompany\MyPlugin` with a namespace unique to your plugin.

## 2. Install and prefix the package

```bash
composer require doiftrue/litewire-di
mkdir -p bin
curl -fL https://github.com/BrianHenryIE/strauss/releases/download/0.29.0/strauss.phar -o bin/strauss.phar
php bin/strauss.phar
```

Strauss creates the prefixed copy and its autoloader in `vendor-prefixed/`. The original package stays in `vendor/`.

## 3. Use the prefixed container

In your plugin's main file, load the generated autoloader and use the new namespace:

```php
<?php

use MyCompany\MyPlugin\Vendor\Kama\LiteWireDI\Container;

require_once __DIR__ . '/vendor-prefixed/autoload.php';

class Greeting {
	public function message(): string {
		return 'Hello!';
	}
}

$container = new Container();
echo $container->get( Greeting::class )->message(); // Hello!
```

Include `vendor-prefixed/` in the plugin you distribute. Run `php bin/strauss.phar` again after installing or updating dependencies to regenerate the prefixed copy. The PHAR is only needed during development; see the [Strauss documentation](https://github.com/BrianHenryIE/strauss#installation) for other installation and automation options.
