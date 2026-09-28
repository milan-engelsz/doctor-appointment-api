<?php

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-09-02 12:00:00');
});

function freeSlotMap(int $doctorId, string $startsAt, string $endsAt): array
{
    return [
        'doctor_id' => $doctorId,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ];
}

/**
 * @return array<int, array{doctor_id: int, starts_at: string, ends_at: string}>
 */
function morningFreeSlotMaps(int $doctorId): array
{
    return [
        freeSlotMap($doctorId, '2026-09-03 09:00:00', '2026-09-03 09:30:00'),
        freeSlotMap($doctorId, '2026-09-03 09:30:00', '2026-09-03 10:00:00'),
        freeSlotMap($doctorId, '2026-09-03 10:00:00', '2026-09-03 10:30:00'),
        freeSlotMap($doctorId, '2026-09-03 10:30:00', '2026-09-03 11:00:00'),
    ];
}

function nextDayMorningFreeSlotMaps(int $doctorId): array
{
    return [
        freeSlotMap($doctorId, '2026-09-04 09:00:00', '2026-09-04 09:30:00'),
        freeSlotMap($doctorId, '2026-09-04 09:30:00', '2026-09-04 10:00:00'),
        freeSlotMap($doctorId, '2026-09-04 10:00:00', '2026-09-04 10:30:00'),
        freeSlotMap($doctorId, '2026-09-04 10:30:00', '2026-09-04 11:00:00'),
    ];
}

function createDoctorWithMorningAvailability(array $overrides = []): Doctor
{
    $doctor = Doctor::factory()->create();

    Availability::factory()->for($doctor)->create([
        'starts_at' => '2026-09-03 09:00:00',
        'ends_at' => '2026-09-03 11:00:00',
        'slot' => 30,
        ...$overrides,
    ]);

    return $doctor;
}

function createDoctorWithTwoMorningWindows(): Doctor
{
    $doctor = createDoctorWithMorningAvailability();

    Availability::factory()->for($doctor)->create([
        'starts_at' => '2026-09-04 09:00:00',
        'ends_at' => '2026-09-04 11:00:00',
        'slot' => 30,
    ]);

    return $doctor;
}

dataset('invalid free slot filters', [
    'doctor id must exist' => [
        ['doctor_id' => 999],
        ['doctor_id' => 'The selected doctor id is invalid.'],
    ],
    'from must be a date' => [
        ['from' => 'tomorrow'],
        ['from' => 'The from field must match the format Y-m-d.'],
    ],
    'from must not be before today' => [
        ['from' => '2026-09-01'],
        ['from' => 'The from field must be a date after or equal to today.'],
    ],
    'to must not be before from' => [
        ['from' => '2026-09-04', 'to' => '2026-09-03'],
        ['to' => 'The to field must be a date after or equal to from.'],
    ],
]);

describe('index', function () {
    test('returns an empty list when there is no availability', function () {
        $this->getJson(route('free-slots.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', null)
            ->assertJsonPath('meta.to', null);
    });

    test('returns the free slots of an availability window in order', function () {
        $doctor = createDoctorWithMorningAvailability();

        $this->getJson(route('free-slots.index'))
            ->assertOk()
            ->assertJsonPath('data', morningFreeSlotMaps($doctor->id));
    });

    test('filters free slots by doctor', function () {
        $doctor = createDoctorWithMorningAvailability();
        createDoctorWithMorningAvailability();

        $this->getJson(route('free-slots.index', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertJsonPath('data', morningFreeSlotMaps($doctor->id));
    });

    test('filters free slots by date range', function () {
        $doctor = createDoctorWithTwoMorningWindows();

        $this->getJson(route('free-slots.index', [
            'doctor_id' => $doctor->id,
            'from' => '2026-09-03',
            'to' => '2026-09-03',
        ]))
            ->assertOk()
            ->assertJsonPath('data', morningFreeSlotMaps($doctor->id));
    });

    test('filters free slots from a start date', function () {
        $doctor = createDoctorWithTwoMorningWindows();

        $this->getJson(route('free-slots.index', [
            'doctor_id' => $doctor->id,
            'from' => '2026-09-04',
        ]))
            ->assertOk()
            ->assertJsonPath('data', nextDayMorningFreeSlotMaps($doctor->id));
    });

    test('filters free slots up to an end date', function () {
        $doctor = createDoctorWithTwoMorningWindows();

        $this->getJson(route('free-slots.index', [
            'doctor_id' => $doctor->id,
            'to' => '2026-09-03',
        ]))
            ->assertOk()
            ->assertJsonPath('data', morningFreeSlotMaps($doctor->id));
    });

    test('omits a slot that has a :dataset appointment', function (string $state) {
        $doctor = createDoctorWithMorningAvailability();

        Appointment::factory()->{$state}()->create([
            'doctor_id' => $doctor->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
        ]);

        $this->getJson(route('free-slots.index', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertJsonPath('data', [
                freeSlotMap($doctor->id, '2026-09-03 09:30:00', '2026-09-03 10:00:00'),
                freeSlotMap($doctor->id, '2026-09-03 10:00:00', '2026-09-03 10:30:00'),
                freeSlotMap($doctor->id, '2026-09-03 10:30:00', '2026-09-03 11:00:00'),
            ]);
    })->with([
        'pending' => 'pending',
        'confirmed' => 'confirmed',
    ]);

    test('includes a slot when its appointment is cancelled', function () {
        $doctor = createDoctorWithMorningAvailability();

        Appointment::factory()->cancelled()->create([
            'doctor_id' => $doctor->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
        ]);

        $this->getJson(route('free-slots.index', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertJsonPath('data', morningFreeSlotMaps($doctor->id));
    });

    test('omits slots that are no longer in the future', function () {
        $this->travelTo('2026-09-03 10:00:00');

        $doctor = createDoctorWithMorningAvailability();

        $this->getJson(route('free-slots.index', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertJsonPath('data', [
                freeSlotMap($doctor->id, '2026-09-03 10:30:00', '2026-09-03 11:00:00'),
            ]);
    });

    test('returns the first page of free slots', function () {
        $doctor = createDoctorWithMorningAvailability([
            'starts_at' => '2026-09-03 08:00:00',
            'ends_at' => '2026-09-03 16:00:00',
        ]);

        $this->getJson(route('free-slots.index', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('data.0', freeSlotMap($doctor->id, '2026-09-03 08:00:00', '2026-09-03 08:30:00'))
            ->assertJsonPath('data.14', freeSlotMap($doctor->id, '2026-09-03 15:00:00', '2026-09-03 15:30:00'));
    });

    test('returns the second page of free slots', function () {
        $doctor = createDoctorWithMorningAvailability([
            'starts_at' => '2026-09-03 08:00:00',
            'ends_at' => '2026-09-03 16:00:00',
        ]);

        $this->getJson(route('free-slots.index', [
            'doctor_id' => $doctor->id,
            'page' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('data', [
                freeSlotMap($doctor->id, '2026-09-03 15:30:00', '2026-09-03 16:00:00'),
            ]);
    });

    test('returns 422 when a free slot filter is invalid', function (array $query, array $errors) {
        $this->getJson(route('free-slots.index', $query))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);
    })->with('invalid free slot filters');
});
