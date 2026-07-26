<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

/**
 * @template-contravariant I
 * @template-covariant O
 */
interface Projector {
    /**
     * @param I $value
     *
     * @return O
     */
    public function project(mixed $value): mixed;
}

/**
 * @implements Projector<int, int>
 */
final readonly class IntProjector implements Projector {
    /**
     * @param int $value
     */
    public function project(mixed $value): int {
        return ($value * 17 + 11) % 1000003;
    }
}

/**
 * @implements Projector<int, int>
 */
final readonly class Doubler implements Projector {
    /**
     * @param int $value
     */
    public function project(mixed $value): int {
        return ($value * 2 + 1) % 1000003;
    }
}
