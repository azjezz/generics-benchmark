<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

/**
 * @template-covariant T
 */
interface Box {
    /**
     * @return T
     */
    public function value(): mixed;
}

/**
 * @template-covariant T
 *
 * @implements Box<T>
 */
abstract readonly class Container implements Box {
    public const TALLY = 7;

    /**
     * @return T
     */
    abstract public function value(): mixed;

    public function tally(): int {
        return static::TALLY;
    }
}
