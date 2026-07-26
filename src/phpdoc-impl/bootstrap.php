<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

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
    if (isset($files[$short])) {
        require __DIR__ . '/' . $files[$short];
    }
});

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
