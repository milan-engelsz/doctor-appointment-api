<?php

namespace App\Rules;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class SameDayAs implements DataAwareRule, ValidationRule
{
    private array $data;

    public function __construct(private readonly string $otherField = 'starts_at') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $otherValue = $this->data[$this->otherField] ?? null;

        if (! $otherValue || ! $value) {
            return;
        }

        try {
            if (! Carbon::parse($value)->isSameDay(Carbon::parse($otherValue))) {
                $fail('The :attribute must be on the same calendar day as :other.')
                    ->translate(['other' => str_replace('_', ' ', $this->otherField)]);
            }
        } catch (InvalidFormatException $exception) {

        }
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
