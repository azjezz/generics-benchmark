<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

/**
 * @template-covariant A
 * @template-covariant B
 * @template-covariant C
 */
final readonly class Triple {
    /**
     * @param A $a
     * @param B $b
     * @param C $c
     */
    public function __construct(
        public mixed $a,
        public mixed $b,
        public mixed $c,
    ) {}

    /**
     * @return Triple<B, C, A>
     */
    public function rotate(): Triple {
        return new Triple($this->b, $this->c, $this->a);
    }
}
