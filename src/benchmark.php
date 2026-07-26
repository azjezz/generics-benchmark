<?php

declare(strict_types=1);

namespace GenericsBenchmark;

const PERFORMANCE_OPCACHE_OPTIONS = [
    '-dopcache.enable=1',
    '-dopcache.enable_cli=1',
    '-dopcache.jit=1205',
    '-dopcache.jit_buffer_size=100M',
    '-dopcache.jit_debug=0',
];

const DEFAULT_ITERATIONS = 20;

const DEFAULT_WARMUP_ITERATIONS = 1;

/**
 * @var array<string, array{directory: string, description: string, language: string}>
 */
const IMPLEMENTATIONS = [
    'phpdoc' => [
        'directory' => 'phpdoc-impl',
        'description' => 'Userland generics via PHPDoc annotations (runs on a stock binary)',
        'language' => 'php',
    ],
    'reified' => [
        'directory' => 'reified-generics-impl',
        'description' => 'Native reified generics, `Foo::<T>` turbofish, variance, generic functions',
        'language' => 'php',
    ],
    'holly' => [
        'directory' => 'holly-generics-impl',
        'description' => 'Native generics, bare `Foo<T>` type arguments, no variance',
        'language' => 'php',
    ],
    'elissa' => [
        'directory' => 'elissa-impl',
        'description' => 'Elissa: a separate language with reified generics, on its own runtime',
        'language' => 'elissa',
    ],
];

/**
 * @var array<string, array{extension: string, modes: list<string>}>
 */
const LANGUAGES = [
    'php' => [
        'extension' => 'php',
        'modes' => ['plain', 'jit'],
    ],
    'elissa' => [
        'extension' => 'els',
        'modes' => ['plain'],
    ],
];

/**
 * @var array<string, array{script: string, description: string}>
 */
const SCENARIOS = [
    'main' => [
        'script' => 'run',
        'description' => 'A loop in which every statement is a generic operation',
    ],
    'specialization' => [
        'script' => 'specialization',
        'description' => 'Many distinct specializations of the same generic classes',
    ],
];

/**
 * @var array<string, list<string>>
 */
const MODES = [
    'plain' => [],
    'jit' => PERFORMANCE_OPCACHE_OPTIONS,
];

/**
 * @var array<int, string>
 */
const SIGNALS = [
    131 => 'SIGQUIT',
    132 => 'SIGILL',
    134 => 'SIGABRT',
    136 => 'SIGFPE',
    137 => 'SIGKILL',
    139 => 'SIGSEGV',
    141 => 'SIGPIPE',
];

function usage(): string
{
    $implementations = '';
    foreach (IMPLEMENTATIONS as $name => $implementation) {
        $implementations .= sprintf("      %-10s %s\n", $name, $implementation['description']);
    }

    $scenarios = '';
    foreach (SCENARIOS as $name => $scenario) {
        $scenarios .= sprintf("      %-16s %s\n", $name, $scenario['description']);
    }

    $binaryOptions = '';
    foreach (array_keys(IMPLEMENTATIONS) as $name) {
        $binaryOptions .= sprintf(
            "      --%-24s Binary to use for the `%s` implementation.\n",
            $name . '-binary=<path>',
            $name,
        );
    }

    $modes = implode(', ', array_keys(MODES));

    return <<<EOT
        Usage: php benchmark.php [options] [implementation...]

        Benchmarks one or more generics implementations. With no implementation
        named, the ones that have a binary to run them are benchmarked: a blanket
        --binary covers every implementation, otherwise only those given a
        --<name>-binary of their own. Naming an implementation always runs it.

        An implementation that cannot run, or that runs but produces the wrong
        answer, is reported as such and the rest of the benchmark carries on.

        Implementations:
        {$implementations}
        Scenarios:
        {$scenarios}
        Modes:
              plain            No extra ini settings.
              jit              Opcache and JIT enabled.

        Options:
              --binary=<path>            Binary to use for PHP implementations without a specific
                                         one (default: the binary running this script).
        {$binaryOptions}      --baseline=<name>          Implementation the others are compared against, and
                                         whose answer is taken as correct (default: the first one
                                         benchmarked).
              --scenario=<name>          Run one scenario instead of all of them.
              --mode=<name>              Run one mode instead of all of them ({$modes}).
              --iterations=<n>           Number of measured iterations (default: 20).
              --warmup-iterations=<n>    Number of warmup iterations before measuring (default: 1).
              --list                     List the available implementations and exit.
              --help                     Show this help message.

        Options must come before the implementation names.

        Examples:
          php benchmark.php --list
          php benchmark.php phpdoc
          php benchmark.php --binary=/path/to/php-generics reified holly
          php benchmark.php --reified-binary=/path/to/php-generics --baseline=phpdoc phpdoc reified
        EOT;
}

function fail(string $message): never
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit(1);
}

function progress(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
}

/**
 * @param list<string> $arguments
 *
 * @return array{seconds: float, status: int, output: string}
 */
function execute(string $binary, array $arguments, string $script): array
{
    $command = escapeshellarg($binary);
    foreach ($arguments as $argument) {
        $command .= ' ' . escapeshellarg($argument);
    }

    $command .= ' ' . escapeshellarg($script) . ' 2>&1';

    $output = [];
    $status = 0;

    $start = hrtime(true);
    exec($command, $output, $status);
    $seconds = (hrtime(true) - $start) / 1_000_000_000;

    return [
        'seconds' => $seconds,
        'status' => $status,
        'output' => trim(implode(PHP_EOL, $output)),
    ];
}

/**
 * @return array{checksum: string, loop: float, memory: string, statics: string}|null
 */
function measurements(string $output): ?array
{
    $values = [];
    foreach (explode(PHP_EOL, $output) as $line) {
        $separator = strpos($line, '=');
        if (false !== $separator) {
            $values[substr($line, 0, $separator)] = substr($line, $separator + 1);
        }
    }

    foreach (['checksum', 'loop', 'memory', 'statics'] as $key) {
        if (!isset($values[$key])) {
            return null;
        }
    }

    return [
        'checksum' => $values['checksum'],
        'loop' => (float) $values['loop'],
        'memory' => $values['memory'],
        'statics' => $values['statics'],
    ];
}

/**
 * Describes why a run did not succeed, in one line.
 */
function diagnosis(int $status, string $output): string
{
    $reason = SIGNALS[$status] ?? null;
    $prefix = null === $reason ? sprintf('exit %d', $status) : sprintf('killed by %s', $reason);

    foreach (explode(PHP_EOL, $output) as $line) {
        $line = trim($line);
        if ('' !== $line) {
            return $prefix . ': ' . $line;
        }
    }

    return $prefix;
}

/**
 * @param non-empty-list<float> $samples
 *
 * @return array{mean: float, min: float, median: float, max: float, stddev: float}
 */
function summarize(array $samples): array
{
    sort($samples);

    $count = count($samples);
    $mean = array_sum($samples) / $count;

    $variance = 0.0;
    foreach ($samples as $sample) {
        $variance += ($sample - $mean) ** 2;
    }

    $middle = intdiv($count, 2);
    $median = ($count % 2) === 0 ? ($samples[$middle - 1] + $samples[$middle]) / 2 : $samples[$middle];

    return [
        'mean' => $mean,
        'min' => $samples[0],
        'median' => $median,
        'max' => $samples[$count - 1],
        'stddev' => sqrt($variance / $count),
    ];
}

function seconds(float $value): string
{
    return number_format($value, 6) . 's';
}

function bytes(string $value): string
{
    if (!ctype_digit($value)) {
        return $value;
    }

    return number_format((int) $value / 1048576, 2) . ' MB';
}

/**
 * @param non-empty-list<list<string>> $rows The first row is the header.
 */
function table(array $rows): string
{
    $widths = [];
    foreach ($rows as $row) {
        foreach ($row as $column => $cell) {
            $widths[$column] = max($widths[$column] ?? 0, strlen($cell));
        }
    }

    $rendered = '';
    foreach ($rows as $row) {
        $cells = [];
        foreach ($row as $column => $cell) {
            $cells[] = str_pad($cell, $widths[$column]);
        }

        $rendered .= rtrim('  ' . implode('  ', $cells)) . PHP_EOL;
    }

    return $rendered;
}

$longOptions = ['binary:', 'baseline:', 'scenario:', 'mode:', 'iterations:', 'warmup-iterations:', 'list', 'help'];
foreach (array_keys(IMPLEMENTATIONS) as $name) {
    $longOptions[] = $name . '-binary:';
}

$restIndex = 0;
$options = getopt('', $longOptions, $restIndex);
if (false === $options) {
    fail('Failed to parse the command line options.' . PHP_EOL . usage());
}

if (isset($options['help'])) {
    echo usage() . PHP_EOL;
    exit(0);
}

if (isset($options['list'])) {
    $rows = [['NAME', 'LANGUAGE', 'DIRECTORY', 'DESCRIPTION']];
    foreach (IMPLEMENTATIONS as $name => $implementation) {
        $rows[] = [
            $name,
            $implementation['language'],
            $implementation['directory'],
            $implementation['description'],
        ];
    }

    echo table($rows);
    exit(0);
}

$selected = array_slice($argv, $restIndex);
foreach ($selected as $name) {
    if (!isset(IMPLEMENTATIONS[$name])) {
        fail(sprintf(
            'Unknown implementation `%s`, expected one of: %s.',
            $name,
            implode(', ', array_keys(IMPLEMENTATIONS)),
        ));
    }
}

$selected = array_values(array_unique($selected));

if ([] === $selected) {
    $configured = [];
    $skipped = [];
    foreach (array_keys(IMPLEMENTATIONS) as $name) {
        if (isset($options[$name . '-binary'])) {
            $configured[] = $name;
        } else {
            $skipped[] = $name;
        }
    }

    if (isset($options['binary']) || [] === $configured) {
        $selected = array_keys(IMPLEMENTATIONS);
    } else {
        $selected = $configured;

        foreach ($skipped as $name) {
            progress(sprintf(
                'Skipping %s: no binary given (pass --%s-binary=<path>, --binary=<path>, or name it explicitly).',
                $name,
                $name,
            ));
        }
    }
}

$baseline = $options['baseline'] ?? $selected[0];
if (!in_array($baseline, $selected, true)) {
    fail(sprintf('The baseline `%s` is not among the implementations being benchmarked.', $baseline));
}

$scenarios = array_keys(SCENARIOS);
if (isset($options['scenario'])) {
    if (!isset(SCENARIOS[$options['scenario']])) {
        fail(sprintf(
            'Unknown scenario `%s`, expected one of: %s.',
            $options['scenario'],
            implode(', ', array_keys(SCENARIOS)),
        ));
    }

    $scenarios = [$options['scenario']];
}

$modes = array_keys(MODES);
if (isset($options['mode'])) {
    if (!isset(MODES[$options['mode']])) {
        fail(sprintf('Unknown mode `%s`, expected one of: %s.', $options['mode'], implode(', ', array_keys(MODES))));
    }

    $modes = [$options['mode']];
}

$iterations = isset($options['iterations']) ? (int) $options['iterations'] : DEFAULT_ITERATIONS;
if ($iterations < 1) {
    fail('--iterations must be at least 1.');
}

$warmupIterations = isset($options['warmup-iterations'])
    ? (int) $options['warmup-iterations']
    : DEFAULT_WARMUP_ITERATIONS;
if ($warmupIterations < 0) {
    fail('--warmup-iterations cannot be negative.');
}

$defaultBinary = $options['binary'] ?? PHP_BINARY;

$binaries = [];
$runnable = [];
foreach ($selected as $name) {
    $binary = $options[$name . '-binary'] ?? null;
    if (null === $binary && 'php' === IMPLEMENTATIONS[$name]['language']) {
        $binary = $defaultBinary;
    }

    if (null === $binary) {
        progress(sprintf('Skipping %s: it needs its own binary (pass --%s-binary=<path>).', $name, $name));

        continue;
    }

    $binaries[$name] = $binary;
    $runnable[] = $name;
}

$selected = $runnable;

if ([] === $selected) {
    fail('No implementation has a binary to run it.');
}

if (!in_array($baseline, $selected, true)) {
    if (isset($options['baseline'])) {
        fail(sprintf('The baseline `%s` has no binary to run it.', $baseline));
    }

    $baseline = $selected[0];
}

$results = [];
foreach ($scenarios as $scenario) {
    foreach ($selected as $name) {
        $language = LANGUAGES[IMPLEMENTATIONS[$name]['language']];

        $script = sprintf(
            '%s/%s/%s.%s',
            __DIR__,
            IMPLEMENTATIONS[$name]['directory'],
            SCENARIOS[$scenario]['script'],
            $language['extension'],
        );

        if (!is_file($script)) {
            progress(sprintf('Skipping %s/%s: the implementation has no such scenario.', $scenario, $name));

            continue;
        }

        foreach ($modes as $mode) {
            if (!in_array($mode, $language['modes'], true)) {
                progress(sprintf(
                    'Skipping %s/%s [%s]: the %s runtime has no such mode.',
                    $scenario,
                    $name,
                    $mode,
                    IMPLEMENTATIONS[$name]['language'],
                ));

                continue;
            }

            $binary = $binaries[$name];
            $arguments = MODES[$mode];

            $smoke = execute($binary, $arguments, $script);
            if (0 !== $smoke['status']) {
                progress(sprintf('%s/%s [%s] is broken, continuing.', $scenario, $name, $mode));

                $results[$scenario][$name][$mode] = [
                    'verdict' => 'broken',
                    'diagnosis' => diagnosis($smoke['status'], $smoke['output']),
                ];

                continue;
            }

            $measurements = measurements($smoke['output']);
            if (null === $measurements) {
                progress(sprintf('%s/%s [%s] produced unreadable output, continuing.', $scenario, $name, $mode));

                $results[$scenario][$name][$mode] = [
                    'verdict' => 'broken',
                    'diagnosis' => 'did not report measurements: ' . diagnosis($smoke['status'], $smoke['output']),
                ];

                continue;
            }

            progress(sprintf(
                'Benchmarking %s/%s [%s] with %s (%d warmup, %d measured)...',
                $scenario,
                $name,
                $mode,
                $binary,
                $warmupIterations,
                $iterations,
            ));

            for ($i = 0; $i < $warmupIterations; $i++) {
                execute($binary, $arguments, $script);
            }

            $wall = [];
            $loop = [];
            $broken = null;
            for ($i = 0; $i < $iterations; $i++) {
                $run = execute($binary, $arguments, $script);
                $sample = 0 === $run['status'] ? measurements($run['output']) : null;
                if (null === $sample) {
                    $broken = diagnosis($run['status'], $run['output']);

                    break;
                }

                $wall[] = $run['seconds'];
                $loop[] = $sample['loop'];
            }

            if (null !== $broken) {
                progress(sprintf('%s/%s [%s] became broken mid-benchmark, continuing.', $scenario, $name, $mode));

                $results[$scenario][$name][$mode] = [
                    'verdict' => 'broken',
                    'diagnosis' => 'failed part way through the benchmark: ' . $broken,
                ];

                continue;
            }

            $results[$scenario][$name][$mode] = [
                'verdict' => 'ran',
                'checksum' => $measurements['checksum'],
                'memory' => $measurements['memory'],
                'statics' => $measurements['statics'],
                'wall' => summarize($wall),
                'loop' => summarize($loop),
            ];
        }
    }
}

$expected = [];
foreach ($scenarios as $scenario) {
    foreach ($modes as $mode) {
        $result = $results[$scenario][$baseline][$mode] ?? null;
        if (null !== $result && 'ran' === $result['verdict']) {
            $expected[$scenario] = $result['checksum'];

            break;
        }
    }
}

foreach ($results as $scenario => $implementations) {
    foreach ($implementations as $name => $byMode) {
        foreach ($byMode as $mode => $result) {
            if ('ran' !== $result['verdict']) {
                continue;
            }

            if (!isset($expected[$scenario])) {
                $results[$scenario][$name][$mode]['verdict'] = 'unchecked';

                continue;
            }

            $results[$scenario][$name][$mode]['verdict'] = $result['checksum'] === $expected[$scenario]
                ? 'ok'
                : 'wrong';
        }
    }
}

echo PHP_EOL;

foreach ($scenarios as $scenario) {
    $header = ['IMPLEMENTATION', 'MODE', 'WALL', 'LOOP', 'MEMORY', 'STATICS', 'VS ' . strtoupper($baseline), 'RESULT'];
    $rows = [$header];

    foreach ($selected as $name) {
        foreach ($modes as $mode) {
            $result = $results[$scenario][$name][$mode] ?? null;
            if (null === $result) {
                continue;
            }

            if ('broken' === $result['verdict']) {
                $rows[] = [$name, $mode, '-', '-', '-', '-', '-', 'BROKEN'];

                continue;
            }

            $reference = $results[$scenario][$baseline][$mode] ?? null;
            $comparison = '-';
            if ($name === $baseline) {
                $comparison = 'baseline';
            } elseif (null !== $reference && 'broken' !== $reference['verdict']) {
                $ratio = $result['loop']['mean'] / $reference['loop']['mean'];
                $comparison = $ratio < 1
                    ? number_format(1 / $ratio, 2) . 'x faster'
                    : number_format($ratio, 2) . 'x slower';
            }

            $rows[] = [
                $name,
                $mode,
                seconds($result['wall']['mean']),
                seconds($result['loop']['mean']),
                bytes($result['memory']),
                $result['statics'],
                $comparison,
                'wrong' === $result['verdict'] ? 'WRONG RESULTS' : strtoupper($result['verdict']),
            ];
        }
    }

    echo sprintf('Scenario `%s` — %s', $scenario, SCENARIOS[$scenario]['description']) . PHP_EOL;
    echo sprintf('%d iterations. WALL is the whole process, LOOP is the measured region inside it.', $iterations)
        . PHP_EOL;
    echo PHP_EOL;
    echo table($rows);
    echo PHP_EOL;
}

$broken = [];
$wrong = [];
foreach ($results as $scenario => $implementations) {
    foreach ($implementations as $name => $byMode) {
        foreach ($byMode as $mode => $result) {
            if ('broken' === $result['verdict']) {
                $broken[$name][] = sprintf('  %s/%s [%s]: %s', $scenario, $name, $mode, $result['diagnosis']);
            } elseif ('wrong' === $result['verdict']) {
                $wrong[$name][] = sprintf(
                    '  %s/%s [%s]: reported %s, expected %s',
                    $scenario,
                    $name,
                    $mode,
                    $result['checksum'],
                    $expected[$scenario],
                );
            }
        }
    }
}

if ([] === $broken && [] === $wrong) {
    echo sprintf('Every implementation ran and agreed with `%s` in every scenario and mode.', $baseline) . PHP_EOL;
    exit(0);
}

foreach ($wrong as $name => $lines) {
    echo sprintf('Implementation `%s` reported wrong results:', $name) . PHP_EOL;
    echo implode(PHP_EOL, $lines) . PHP_EOL;
    echo PHP_EOL;
}

foreach ($broken as $name => $lines) {
    echo sprintf('Implementation `%s` is broken:', $name) . PHP_EOL;
    echo implode(PHP_EOL, $lines) . PHP_EOL;
    echo PHP_EOL;
}

echo 'Timings for the rows above are still valid; the failing rows are excluded.' . PHP_EOL;
