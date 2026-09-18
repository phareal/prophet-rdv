<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

final class ValidationResult
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $errors,
        private readonly array $data,
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }
}
