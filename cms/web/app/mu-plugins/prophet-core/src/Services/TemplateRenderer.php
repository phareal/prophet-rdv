<?php

declare(strict_types=1);

namespace ProphetCore\Services;

interface TemplateRenderer
{
    /** @param array<string, mixed> $data */
    public function render(string $view, array $data): string;
}
