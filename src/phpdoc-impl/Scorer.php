<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

interface Scorer {
    public function score(): int;
}

final readonly class Weight implements Scorer {
    public function __construct(
        private int $weight,
    ) {}

    public function score(): int {
        return $this->weight;
    }
}

/**
 * @template T of Scorer
 */
final readonly class Scored {
    /**
     * @param T $subject
     */
    public function __construct(
        private Scorer $subject,
    ) {}

    public function total(): int {
        return $this->subject->score();
    }
}
