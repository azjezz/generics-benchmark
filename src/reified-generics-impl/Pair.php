<?php

declare(strict_types=1);

namespace GenericsBenchmark\ReifiedImpl;

final readonly class Pair<out A, out B> {
    public function __construct(
        public A $first,
        public B $second,
    ) {}

    public function withFirst<U>(U $value): Pair<U, B> {
        return new Pair::<U, B>($value, $this->second);
    }

    public function swap(): Pair<B, A> {
        return new Pair::<B, A>($this->second, $this->first);
    }
}
