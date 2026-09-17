<?php

namespace App\Wizard;

final readonly class Button
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}
}
