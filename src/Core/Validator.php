<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function required(string $field, string $message = 'Verplicht veld'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function email(string $field, string $message = 'Geen geldig e-mailadres'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function min(string $field, int $length, ?string $message = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) < $length) {
            $this->errors[$field][] = $message ?? "Minimaal {$length} tekens";
        }
        return $this;
    }

    public function max(string $field, int $length, ?string $message = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $length) {
            $this->errors[$field][] = $message ?? "Maximaal {$length} tekens";
        }
        return $this;
    }

    public function matches(string $field, string $other, string $message = 'Komt niet overeen'): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$other] ?? null)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
