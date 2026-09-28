<?php

namespace App\Data;

use Carbon\CarbonInterface;

final readonly class FreeSlot
{
    public function __construct(
        public int $doctor_id,
        public CarbonInterface $startsAt,
        public CarbonInterface $endsAt,
    ) {}
}
