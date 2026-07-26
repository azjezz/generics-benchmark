<?php

declare(strict_types=1);

namespace GenericsBenchmark\HollyImpl;

final class Registry<T> {
    public const STRIDE = 2;

    public static int $made = 0;

    public static function bump(): int {
        return ++static::$made;
    }
}

final class Cells {
    public static function of<U>(U $value): Cell<U> {
        return new Cell<U>($value);
    }
}

final class Runner {
    public static function project<A, B, C>(Pair<A, C> $input, Projector<A, B> $projector): Pair<B, C> {
        return new Pair<B, C>($projector->project($input->first), $input->second);
    }
}
