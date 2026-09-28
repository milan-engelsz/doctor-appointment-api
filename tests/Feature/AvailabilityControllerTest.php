<?php

use App\Models\Availability;
use App\Models\Doctor;
use App\Support\ApiDateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-09-02 12:00:00');
});

function availabilityResourceMap(Availability $availability): array
{
    return [
        'id' => $availability->id,
        'doctor_id' => $availability->doctor_id,
        'starts_at' => $availability->starts_at->format(ApiDateTime::FORMAT),
        'ends_at' => $availability->ends_at->format(ApiDateTime::FORMAT),
        'slot' => $availability->slot,
    ];
}

function validAvailabilityPayload(array $overrides = []): array
{
    return [
        'starts_at' => '2026-09-24 08:15:00',
        'ends_at' => '2026-09-24 16:15:00',
        'slot' => 30,
        ...$overrides,
    ];
}

dataset('invalid availability payloads', function () {
    $valid = validAvailabilityPayload();

    return [
        'starts_at is required' => [
            Arr::except($valid, 'starts_at'),
            ['starts_at' => 'The starts at field is required.'],
        ],
        'starts_at must be a date' => [
            [...$valid, 'starts_at' => 'test'],
            ['starts_at' => 'The starts at field must match the format '.ApiDateTime::FORMAT],
        ],
        'starts_at must be after now' => [
            [...$valid, 'starts_at' => '2026-09-01 07:15:00'],
            ['starts_at' => 'The starts at field must be a date after now.'],
        ],

        'ends_at is required' => [
            Arr::except($valid, 'ends_at'),
            ['ends_at' => 'The ends at field is required.'],
        ],
        'ends_at must be a date' => [
            [...$valid, 'ends_at' => 'test'],
            ['ends_at' => 'The ends at field must match the format '.ApiDateTime::FORMAT],
        ],
        'ends_at must be after starts at' => [
            [...$valid, 'starts_at' => '2027-09-01 08:15:00', 'ends_at' => '2027-09-01 07:15:00'],
            ['ends_at' => 'The ends at field must be a date after starts at.'],
        ],
        'ends_at must be same day as starts_at' => [
            [...$valid, 'starts_at' => '2027-09-01 08:15:00', 'ends_at' => '2027-09-02 08:15:00'],
            ['ends_at' => 'The ends at must be on the same calendar day as starts at.'],
        ],
        'ends_at must be (starts_at + 30) % slot == 0' => [
            [...$valid, 'starts_at' => '2026-10-01 08:15:00', 'ends_at' => '2026-10-01 09:00:00', 'slot' => 30],
            ['ends_at' => 'The ends at must make the availability length a multiple of the slot duration (30 minutes).'],
        ],

        'slot is required' => [
            Arr::except($valid, 'slot'),
            ['slot' => 'The slot field is required.'],
        ],
        'slot must be a integer' => [
            [...$valid, 'slot' => 'test'],
            ['slot' => 'The slot field must be an integer.'],
        ],
        'slot must be at least 30' => [
            [...$valid, 'slot' => 29],
            ['slot' => 'The slot field must be at least 30.'],
        ],
        'slot must be up to 120' => [
            [...$valid, 'slot' => 121],
            ['slot' => 'The slot field must not be greater than 120.'],
        ],

        'all fields are required' => [
            [],
            [
                'starts_at' => 'The starts at field is required.',
                'ends_at' => 'The ends at field is required.',
                'slot' => 'The slot field is required.',
            ],
        ],
    ];
});

describe('index', function () {

    test('return selected doctor first 15 availability', function () {
        Doctor::factory()
            ->has(Availability::factory()->count(10))
            ->create();

        $doctor = Doctor::factory()
            ->has(Availability::factory()->count(16))
            ->create();

        $response = $this->getJson(route('doctors.availabilities.index', $doctor));

        $response
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', 1)
            ->assertJsonPath('meta.to', 15)
            ->assertJsonPath('data', $doctor->availabilities()->limit(15)->get()->map(
                fn (Availability $availability) => availabilityResourceMap($availability)
            )->toArray());

    });

    test('returns the empty availability list', function () {
        $doctor = Doctor::factory()->create();

        $response = $this->getJson(route('doctors.availabilities.index', $doctor));

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', null)
            ->assertJsonPath('meta.to', null);
    });

    test('returns 404 when the doctor does not exist', function () {
        $this->getJson(route('doctors.availabilities.index', 1))
            ->assertNotFound();
    });
});

describe('store', function () {
    test('rejects the payload when a validation rule fails', function (array $payload, array $errors) {
        $doctor = Doctor::factory()->create();

        $this->postJson(route('doctors.availabilities.store', $doctor), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseCount('availabilities', 0);
    })->with('invalid availability payloads');

    test('returns 404 when the doctor does not exist', function () {
        $this->postJson(route('doctors.availabilities.store', 1))
            ->assertNotFound();
    });

    test('creates a availability and returns the resource map', function () {
        $doctor = Doctor::factory()->create();

        $payload = validAvailabilityPayload();

        $response = $this->postJson(route('doctors.availabilities.store', $doctor), $payload);

        $availability = $doctor->availabilities()->sole();

        $response
            ->assertCreated()
            ->assertExactJson([
                'data' => availabilityResourceMap($availability),
            ]);

        $this->assertDatabaseHas('availabilities', $payload);
    });

    test('rejects the payload when a overlapping', function (array $payload, array $errors) {
        $doctor = Doctor::factory()->create();

        $doctor->availabilities()->create(validAvailabilityPayload([
            'starts_at' => '2026-09-03 12:00:00',
            'ends_at' => '2026-09-03 14:00:00',
        ]));

        $this->postJson(route('doctors.availabilities.store', $doctor), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseCount('availabilities', 1);
    })->with(function () {
        $valid = validAvailabilityPayload();

        return [
            'starts_at_overpapping' => [
                [...$valid, 'starts_at' => '2026-09-03 13:00:00', 'ends_at' => '2026-09-03 16:30:00'],
                [
                    'starts_at' => 'The starts at field overlaps an existing availability window for this doctor.',
                ],
            ],
            'ends_at_overpapping' => [
                [...$valid, 'starts_at' => '2026-09-03 09:00:00', 'ends_at' => '2026-09-03 12:30:00'],
                [
                    'ends_at' => 'The ends at field overlaps an existing availability window for this doctor.',
                ],
            ],
            'starts_and_ends_at_overpapping' => [
                [...$valid, 'starts_at' => '2026-09-03 12:30:00', 'ends_at' => '2026-09-03 13:30:00'],
                [
                    'starts_at' => 'The starts at field overlaps an existing availability window for this doctor.',
                    'ends_at' => 'The ends at field overlaps an existing availability window for this doctor.',
                ],
            ],
        ];
    });

    test('creates an availability that starts when another one ends', function () {
        $doctor = Doctor::factory()->create();

        $doctor->availabilities()->create(validAvailabilityPayload([
            'starts_at' => '2026-09-03 09:00:00',
            'ends_at' => '2026-09-03 12:00:00',
        ]));

        $payload = validAvailabilityPayload([
            'starts_at' => '2026-09-03 12:00:00',
            'ends_at' => '2026-09-03 15:00:00',
        ]);

        $this->postJson(route('doctors.availabilities.store', $doctor), $payload)
            ->assertCreated();

        $this->assertDatabaseCount('availabilities', 2);
        $this->assertDatabaseHas('availabilities', [
            'doctor_id' => $doctor->id,
            ...$payload,
        ]);
    });

    test('creates the same availability window for another doctor', function () {
        $payload = validAvailabilityPayload([
            'starts_at' => '2026-09-03 09:00:00',
            'ends_at' => '2026-09-03 12:00:00',
        ]);

        $existingDoctor = Doctor::factory()->create();
        $existingDoctor->availabilities()->create($payload);

        $doctor = Doctor::factory()->create();

        $this->postJson(route('doctors.availabilities.store', $doctor), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('availabilities', [
            'doctor_id' => $existingDoctor->id,
            ...$payload,
        ]);
        $this->assertDatabaseHas('availabilities', [
            'doctor_id' => $doctor->id,
            ...$payload,
        ]);
    });
});
