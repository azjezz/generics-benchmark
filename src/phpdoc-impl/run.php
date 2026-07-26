<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

require __DIR__ . '/bootstrap.php';

const ITERATIONS = 300000;

$projector = new IntProjector();
$doubler = new Doubler();
$weight = new Weight(3);

Registry::bump();
Registry::bump();
Registry::bump();
$statics = statics(Registry::$made, Registry::$made);

$sink = 0;
$index = 0;

$start = hrtime(true);
while ($index < ITERATIONS) {
    $cell = new Cell($index);
    $doubled = $cell->map($doubler);
    $shifted = $doubled->map($projector);
    $nested = new Cell($doubled);
    $inner = $nested->value();
    $pair = new Pair($shifted->value(), 'generic');
    $moved = Runner::project($pair, $doubler);
    $swapped = $moved->swap();
    $refit = $swapped->withFirst($inner->value());
    $triple = new Triple($refit->first, $moved->second, $cell);
    $rotated = $triple->rotate();
    $bagged = Cells::of($doubled);
    $scored = new Scored($weight);
    $boxed = $bagged instanceof Box;

    $sink += $rotated->c + $scored->total() + ($boxed ? $bagged->tally() : 0);

    $index++;
}

$elapsed = (hrtime(true) - $start) / 1_000_000_000;

report($sink % 1000000007, $elapsed, $statics);
