<?php

namespace App\Domain\Scheduling\Data;

readonly class ClassroomSnapshot
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $capacity,
        public bool $active = true,
    ) {}
}
