<?php

namespace App\Rules;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class EndsAtFitsSlotDuration implements DataAwareRule, ValidationRule
{
    private array $data;

    public function __construct(private readonly string $startDateField = 'starts_at', private readonly string $slotField = 'slot') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $startDate = $this->data[$this->startDateField] ?? null;
        $slot = $this->data[$this->slotField] ?? null;

        if (! $startDate || ! $slot || ! $value) {
            return;
        }

        if (! is_numeric($slot) || $slot < 1) {
            return;
        }

        try {
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($value);
            $diffInMinutes = $startDate->diffInMinutes($endDate);

            if ($diffInMinutes % $slot !== 0) {
                $fail('The :attribute must make the availability length a multiple of the slot duration (:slot minutes).')
                    ->translate(['slot' => $slot]);
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
