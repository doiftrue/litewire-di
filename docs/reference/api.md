# API reference

`Kama\LiteWireDI\Container` has four public operations.

| Method | Result |
| --- | --- |
| `has( string $id ): bool` | Checks whether an ID is registered, stored, or autowireable. |
| `set( string $id, $service ): void` | Registers an object, class name, closure factory, or named parameters. |
| `get( string $id )` | Resolves and stores a shared object. |
| `make( string $id, array $parameters = [] )` | Resolves a fresh object without storing the root object. |

## IDs and definitions

An ID must name an existing class or interface. `set()` accepts:

- an existing object;
- an existing class name;
- a `Closure` factory that returns an object;
- an associative array of named parameters for an instantiable class.

Invalid IDs and invalid definitions cause `InvalidArgumentException` in `set()`. Unresolvable IDs cause `NotFoundException` or `ContainerException` when resolved.

## Autowiring rules

The container resolves typed class dependencies recursively. It cannot choose an implementation for an interface or abstract class, and it cannot invent required scalar values. Bind an implementation with `set()`, or pass scalar values through configuration or `make()`.

The container itself is registered as `Container::class`, so a constructor can request it. Prefer requesting a specific dependency in normal application classes; keep container access at the composition root where possible.

Circular resolutions throw `ContainerException` and include the resolution chain.
