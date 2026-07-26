<?php

declare(strict_types=1);

namespace GenericsBenchmark\ReifiedImpl;

interface Box<out T> {
    public function value(): T;
}

abstract readonly class Container<out T> implements Box<T> {
    public const TALLY = 7;

    abstract public function value(): T;

    public function tally(): int {
        return static::TALLY;
    }
}
