# Limitations and troubleshooting

LiteWire DI deliberately keeps its contract small. It has no compiled container, service providers, scopes, tags, standalone scalar storage, string service IDs, configuration format, or public JavaScript API.

## A required scalar cannot be resolved

Give the value a default, configure the concrete class with `set()`, pass it to `make()`, use a factory, or inject a typed configuration object.

## An interface cannot be resolved

The container cannot guess an implementation. Bind it before resolving the consuming service:

```php
$container->set( LoggerInterface::class, FileLogger::class );
```

## A changed definition did not affect an existing service

`get()` returns shared objects. Replacing a definition removes only the stored instance under that exact ID; it does not reconstruct other services that were already built with the old dependency. Configure services before the first `get()`, or create a new container for an isolated setup.

## A circular dependency was reported

Break the cycle in your application design. A service should receive the narrow dependency it needs rather than the service that eventually requests it again.
