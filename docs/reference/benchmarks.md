# Benchmarks

These are the latest detailed LiteWire DI measurements. Timings depend on PHP, hardware, extensions, and system load; compare only runs made in an equivalent environment.

## Latest run

- Date: 2026-07-06
- PHP: 8.5.5
- PHPBench: 1.7.0
- OPcache: enabled
- Xdebug: disabled
- Failures and errors: 0

| Subject | Runs × rounds | Memory peak | Time | Variance |
| --- | ---: | ---: | ---: | ---: |
| `direct_instantiation` | 10,000 × 5 | 666.744 KB | 0.078 μs | ±2.36% |
| `get__cold` | 10,000 × 5 | 16.422 MB | 2.055 μs | ±1.38% |
| `get__stored` | 10,000 × 5 | 666.720 KB | 0.060 μs | ±2.35% |
| `make__reflection__cold` | 10,000 × 5 | 15.862 MB | 1.986 μs | ±1.44% |
| `make__reflection__cached` | 10,000 × 5 | 666.744 KB | 0.799 μs | ±1.57% |
| `make__registered_factory` | 10,000 × 5 | 666.744 KB | 0.419 μs | ±2.68% |
| `get__deep_autowiring__cold` | 10,000 × 5 | 18.467 MB | 2.932 μs | ±0.86% |
| `get__deep_autowiring__stored` | 10,000 × 5 | 666.744 KB | 0.059 μs | ±4.11% |
| `has__resolvable_class__cold` | 10,000 × 5 | 11.356 MB | 2.232 μs | ±2.92% |
| `has__resolvable_class__stored` | 10,000 × 5 | 739.432 KB | 0.120 μs | ±2.64% |

`get__cold` includes reflection, dependency discovery, object construction, and storage of the shared result. `get__stored` measures a later lookup. `make()` always creates a fresh root object; its cached benchmark follows an earlier resolution in the same container.

Run the current suite from the repository root:

```bash
make benchmark
```

The authoritative source for the full benchmark methodology and subject descriptions is [`benchmarks/README.md`](https://github.com/doiftrue/litewire-di/blob/main/benchmarks/README.md).
