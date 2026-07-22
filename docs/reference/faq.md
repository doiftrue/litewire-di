# FAQ and support

## Is LiteWire DI PSR-11 compatible?

Its API is PSR-11-style, but it does not implement `Psr\Container\ContainerInterface` and does not require `psr/container`.

## Does it have a public JavaScript API or WordPress hooks?

No. LiteWire DI is a PHP library. It does not expose JavaScript APIs, WordPress hooks, REST endpoints, or markup contracts.

## Does it compile the container?

No. It resolves objects at runtime and caches reflection metadata within each container instance.

## Where can I get help or report a problem?

Open an issue in the [LiteWire DI GitHub repository](https://github.com/doiftrue/litewire-di/issues). Include your PHP version, the smallest reproducible example, and the complete exception message where applicable.
