<?php

declare(strict_types=1);

namespace GenericsBenchmark\PHPDocImpl;

/**
 * @template T
 *
 * @extends Container<T>
 */
final readonly class Cell extends Container {
    /**
     * @param T $item
     */
    public function __construct(
        private mixed $item,
    ) {}

    /**
     * @return T
     */
    public function value(): mixed {
        return $this->item;
    }

    /**
     * @template U
     *
     * @param Projector<T, U> $projector
     *
     * @return Cell<U>
     */
    public function map(Projector $projector): Cell {
        return new Cell($projector->project($this->item));
    }
}
