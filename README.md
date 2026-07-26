# PHP Generics Benchmark

This repository contains a benchmark for testing the performance of generics in PHP. The benchmark compares the performance of different implementations of generics in PHP.

## Running the Benchmark

```
php src/benchmark.php --scenario=main --iterations=30 \
  --holly-binary=/path/to/holly/php \
  --phpdoc-binary=$(which php) \
  --baseline=phpdoc
```

At least one of the `--phpdoc-binary`, `--holly-binary`, `--reified-binary`, or `--elissa-binary` options must be provided. The benchmark will run the specified implementations and compare their performance.

Available scenarios are:

- `main`: The main benchmark scenario that tests the performance of generics in PHP.
- `specialization`: A scenario that tests the performance of generics with specialization.

## Elissa

[Elissa](https://github.com/carthage-software/elissa) is not PHP: it is a separate language with its own runtime, included here because its generics are reified in the same way the `reified` implementation proposes. It is benchmarked alongside the PHP implementations so the two can be read against each other.

```
php src/benchmark.php --scenario=main --iterations=30 \
  --phpdoc-binary=$(which php) \
  --elissa-binary=/path/to/elissa/target/release/elissac \
  --baseline=phpdoc
```

Because it is a different runtime, a few things differ and the harness accounts for them:

- It needs `--elissa-binary`; the blanket `--binary` only covers the PHP implementations, and `elissa` is skipped without a binary of its own.
- It only runs in `plain` mode. The `jit` mode passes opcache ini flags, which mean nothing to it.
- It reports memory as `unknown`, since the runtime exposes no peak-memory reading.
- It has no `specialization` scenario, and is skipped for it ( classes have a limit of 64 methods ).
-
