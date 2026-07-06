# LiteWire DI benchmarks

This report records the latest detailed performance measurements for LiteWire DI. Benchmark results depend on the machine, PHP version, loaded extensions, and background load, so compare runs made in the same environment.

## Latest run

- Date: 2026-07-06
- PHP: 8.5.5
- PHPBench: 1.7.0
- OPcache: enabled
- Xdebug: disabled
- Subjects: 8
- Failures and errors: 0

| Subject                   | Runs × Rounds | Memory peak |     Time | Variance |
|---------------------------|--------------:|------------:|---------:|---------:|
| `direct_instantiation`    |    10,000 × 5 |  666.744 KB | 0.078 μs |   ±2.36% |
| `cold_get`                |    10,000 × 5 |   16.422 MB | 2.055 μs |   ±1.38% |
| `stored_get`              |    10,000 × 5 |  666.720 KB | 0.060 μs |   ±2.35% |
| `cold_reflection_make`    |    10,000 × 5 |   15.862 MB | 1.986 μs |   ±1.44% |
| `cached_reflection_make`  |    10,000 × 5 |  666.744 KB | 0.799 μs |   ±1.57% |
| `registered_factory_make` |    10,000 × 5 |  666.744 KB | 0.419 μs |   ±2.68% |
| `cold_deep_autowiring`    |    10,000 × 5 |   18.467 MB | 2.932 μs |   ±0.86% |
| `stored_deep_autowiring`  |    10,000 × 5 |  666.744 KB | 0.059 μs |   ±4.11% |

## Column legend

- **Subject** — the operation measured by PHPBench.
- **Runs** — the number of times the subject is executed during each round (`@Revs(10000)`).
- **Rounds** — the number of independently measured iterations (`@Iterations(5)`).
- **Memory peak** — the peak memory used by the complete benchmark process while measuring that subject. It is not the per-call allocation.
- **Time** — the modal execution time for one subject call. One microsecond (`μs`) is 0.001 milliseconds.
- **Variance** — relative standard deviation between measured iterations. Lower values indicate a more stable result.

## Subjects

### `direct_instantiation`

Creates `ClassWithDeps` and `SimpleClass` directly with `new`. This is the baseline without container work.

### `cold_get`

Creates a new container and resolves `ClassWithDeps` for the first time. The measurement includes reflection, dependency discovery, object construction, and storage of the shared instance.

### `stored_get`

Returns a previously resolved shared `ClassWithDeps` instance. This measures the container's stored-instance lookup path.

### `cold_reflection_make`

Creates a new container and calls `make()` before reflection metadata has been cached. The result is not stored as a shared service.

### `cached_reflection_make`

Calls `make()` after the same container has already resolved the class once. This measures fresh object creation with cached reflection metadata.

### `registered_factory_make`

Creates `ClassWithDeps` through a registered closure factory. It measures factory invocation and container dispatch without reflection-based construction.

### `cold_deep_autowiring`

Creates a new container and resolves the three-level `ClassDeepA → ClassDeepB → ClassDeepC` graph for the first time.

### `stored_deep_autowiring`

Returns a previously resolved `ClassDeepA` graph. This measures lookup of an already stored top-level shared instance.

## Run locally

From the repository root:

```bash
make benchmark
```

The command installs PHPBench into the isolated `tools/benchmark/vendor` directory and then executes all subjects. Neither benchmark dependencies nor generated lock files are committed.

For meaningful comparisons, keep the PHP version, OPcache setting, hardware, and system load consistent between runs.
