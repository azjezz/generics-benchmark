<?php

declare(strict_types=1);

namespace GenericsBenchmark\ReifiedImpl;

spl_autoload_register(static function (string $class): void {
    static $files = [
        'Box' => 'Box.php',
        'Container' => 'Box.php',
        'Cell' => 'Cell.php',
        'Pair' => 'Pair.php',
        'Triple' => 'Triple.php',
        'Projector' => 'Projector.php',
        'IntProjector' => 'Projector.php',
        'Doubler' => 'Projector.php',
        'Scorer' => 'Scorer.php',
        'Weight' => 'Scorer.php',
        'Scored' => 'Scorer.php',
        'Registry' => 'Ops.php',
        'Cells' => 'Ops.php',
        'Runner' => 'Ops.php',
    ];

    $short = substr($class, strrpos($class, '\\') + 1);
    $arguments = strpos($short, '<');
    if (false !== $arguments) {
        $short = substr($short, 0, $arguments);
    }

    if (isset($files[$short])) {
        require __DIR__ . '/' . $files[$short];
    }
});

/**
 * Classifies what two specializations of the same generic class did to one
 * static property: `Registry<int>` was bumped twice and `Registry<string>` once,
 * so a per-specialization implementation sees 2 and 1 while an implementation
 * that shares one set of statics sees 3 and 3.
 */
function statics(int $int, int $string): string
{
    if (2 === $int && 1 === $string) {
        return 'per-specialization';
    }

    if (3 === $int && 3 === $string) {
        return 'shared';
    }

    return sprintf('unknown(%d,%d)', $int, $string);
}

/**
 * Reports the measurements the harness parses off stdout.
 */
function report(int $checksum, float $loopSeconds, string $statics, ?float $firstPassSeconds = null): void
{
    echo 'checksum=' . $checksum . PHP_EOL;
    echo 'loop=' . number_format($loopSeconds, 6, '.', '') . PHP_EOL;
    if (null !== $firstPassSeconds) {
        echo 'first=' . number_format($firstPassSeconds, 6, '.', '') . PHP_EOL;
    }

    echo 'memory=' . memory_get_peak_usage(false) . PHP_EOL;
    echo 'statics=' . $statics . PHP_EOL;
}
