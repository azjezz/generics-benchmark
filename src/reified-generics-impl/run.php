<?php

declare(strict_types=1);

namespace GenericsBenchmark\ReifiedImpl;

require __DIR__ . '/bootstrap.php';

const ITERATIONS = 300000;

$projector = new IntProjector();
$doubler = new Doubler();
$weight = new Weight(3);

Registry::<int>::bump();
Registry::<int>::bump();
Registry::<string>::bump();
$statics = statics(Registry::<int>::$made, Registry::<string>::$made);

$sink = 0;
$index = 0;

$start = hrtime(true);
while ($index < ITERATIONS) {
    $cell = new Cell::<int>($index);
    $doubled = $cell->map::<int>($doubler);
    $shifted = $doubled->map::<int>($projector);
    $nested = new Cell::<Cell<int>>($doubled);
    $inner = $nested->value();
    $pair = new Pair::<int, string>($shifted->value(), 'generic');
    $moved = Runner::project::<int, int, string>($pair, $doubler);
    $swapped = $moved->swap();
    $refit = $swapped->withFirst::<int>($inner->value());
    $triple = new Triple::<int, string, Cell<int>>($refit->first, $moved->second, $cell);
    $rotated = $triple->rotate();
    $bagged = Cells::of::<Cell<int>>($doubled);
    $scored = new Scored::<Weight>($weight);
    $boxed = $bagged instanceof Box::<Cell<int>>;

    $sink += $rotated->c + $scored->total() + ($boxed ? $bagged->tally() : 0);

    $index++;
}

$elapsed = (hrtime(true) - $start) / 1_000_000_000;

report($sink % 1000000007, $elapsed, $statics);
