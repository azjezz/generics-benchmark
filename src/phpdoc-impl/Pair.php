<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

/**
 * @template-covariant A
 * @template-covariant B
 */
final readonly class Pair {
    /**
     * @param A $first
     * @param B $second
     */
    public function __construct(
        public mixed $first,
        public mixed $second,
    ) {}

    /**
     * @template U
     *
     * @param U $value
     *
     * @return Pair<U, B>
     */
    public function withFirst(mixed $value): Pair {
        return new Pair($value, $this->second);
    }

    /**
     * @return Pair<B, A>
     */
    public function swap(): Pair {
        return new Pair($this->second, $this->first);
    }
}
