<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

/**
 * @template T
 */
final class Registry
{
    public const STRIDE = 2;

    public static int $made = 0;

    public static function bump(): int
    {
        return ++static::$made;
    }
}

final class Cells
{
    /**
     * @template U
     *
     * @param U $value
     *
     * @return Cell<U>
     */
    public static function of(mixed $value): Cell
    {
        return new Cell($value);
    }
}

final class Runner
{
    /**
     * @template A
     * @template B
     * @template C
     *
     * @param Pair<A, C> $input
     * @param Projector<A, B> $projector
     *
     * @return Pair<B, C>
     */
    public static function project(Pair $input, Projector $projector): Pair
    {
        return new Pair($projector->project($input->first), $input->second);
    }
}
