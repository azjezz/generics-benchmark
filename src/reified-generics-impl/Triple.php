<?php

declare(strict_types=1);

namespace GenericsBenchmark\ReifiedImpl;

final readonly class Triple<out A, out B, out C> {
    public function __construct(
        public A $a,
        public B $b,
        public C $c,
    ) {}

    public function rotate(): Triple<B, C, A> {
        return new Triple::<B, C, A>($this->b, $this->c, $this->a);
    }
}
