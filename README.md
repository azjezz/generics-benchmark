# PHP Generics Benchmark

This repository contains a benchmark for testing the performance of generics in PHP. The benchmark compares the performance of different implementations of generics in PHP.

## Running the Benchmark

```
php src/benchmark.php --scenario=main --iterations=30 \
  --holly-binary=/path/to/holly/php \
  --phpdoc-binary=$(which php) \
  --baseline=phpdoc
```

At least one of the `--phpdoc-binary`, `--holly-binary`, or `--reified-binary` options must be provided. The benchmark will run the specified implementations and compare their performance.

Available scenarios are:

- `main`: The main benchmark scenario that tests the performance of generics in PHP.
- `specialization`: A scenario that tests the performance of generics with specialization.
