<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use App\Support\ApiDateTime;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-09-02 12:00:00');
});

function appointmentResourceMap(Appointment $appointment): array
{
    return [
        'id' => $appointment->id,
        'doctor_id' => $appointment->doctor_id,
        'patient_id' => $appointment->patient_id,
        'start_time' => $appointment->start_time->format(ApiDateTime::FORMAT),
        'end_time' => $appointment->end_time->format(ApiDateTime::FORMAT),
        'status' => $appointment->status->value,
        'cancellation_reason' => $appointment->cancellation_reason,
    ];
}

function appointmentStartingAt(string $startTime): AppointmentFactory
{
    $start = CarbonImmutable::createFromFormat(ApiDateTime::FORMAT, $startTime);

    return Appointment::factory()->state([
        'start_time' => $start,
        'end_time' => $start->addMinutes(30),
    ]);
}

function createBookableDoctor(array $overrides = []): Doctor
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

function validAppointmentPayload(Doctor $doctor, Patient $patient, array $overrides = []): array
{
    return [
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'start_time' => '2026-09-03 09:00:00',
        ...$overrides,
    ];
}

function validCancelPayload(array $overrides = []): array
{
    return [
        'cancellation_reason' => 'Patient is unavailable.',
        ...$overrides,
    ];
}

dataset('invalid appointment payloads', function () {
    return [
        'doctor id is required' => [
            [
                'patient_id' => 1,
                'start_time' => '2026-09-03 09:00:00',
            ],
            ['doctor_id' => 'The doctor id field is required.'],
        ],
        'patient id is required' => [
            [
                'doctor_id' => 1,
                'start_time' => '2026-09-03 09:00:00',
            ],
            ['patient_id' => 'The patient id field is required.'],
        ],
        'start time is required' => [
            [
                'doctor_id' => 1,
                'patient_id' => 1,
            ],
            ['start_time' => 'The start time field is required.'],
        ],
        'start time must match the api date format' => [
            [
                'doctor_id' => 1,
                'patient_id' => 1,
                'start_time' => '2026-09-03',
            ],
            ['start_time' => 'The start time field must match the format '.ApiDateTime::FORMAT.'.'],
        ],
        'all fields are required' => [
            [],
            [
                'doctor_id' => 'The doctor id field is required.',
                'patient_id' => 'The patient id field is required.',
                'start_time' => 'The start time field is required.',
            ],
        ],
    ];
});

dataset('invalid cancel payloads', function () {
    $valid = validCancelPayload();

    return [
        'cancellation reason is required' => [
            Arr::except($valid, 'cancellation_reason'),
            ['cancellation_reason' => 'The cancellation reason field is required.'],
        ],
        'cancellation reason must be a string' => [
            [...$valid, 'cancellation_reason' => ['Patient is unavailable.']],
            ['cancellation_reason' => 'The cancellation reason field must be a string.'],
        ],
        'cancellation reason may not be greater than 500 characters' => [
            [...$valid, 'cancellation_reason' => str_repeat('a', 501)],
            ['cancellation_reason' => 'The cancellation reason field must not be greater than 500 characters.'],
        ],
    ];
});

describe('index', function () {
    test('returns the patient appointments in start time order', function () {
        $patient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();

        $later = appointmentStartingAt('2026-09-04 09:00:00')->for($patient)->create();
        $earlier = appointmentStartingAt('2026-09-03 09:00:00')->for($patient)->create();
        appointmentStartingAt('2026-09-03 10:00:00')->for($otherPatient)->create();

        $this->getJson(route('appointments.index', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertJsonPath('data', [
                appointmentResourceMap($earlier),
                appointmentResourceMap($later),
            ]);
    });

    test('filters appointments by status', function () {
        $patient = Patient::factory()->create();

        $confirmed = appointmentStartingAt('2026-09-03 09:00:00')
            ->for($patient)
            ->confirmed()
            ->create();
        appointmentStartingAt('2026-09-03 10:00:00')
            ->for($patient)
            ->pending()
            ->create();

        $this->getJson(route('appointments.index', [
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]))
            ->assertOk()
            ->assertJsonPath('data', [
                appointmentResourceMap($confirmed),
            ]);
    });

    test('returns an empty list when the patient has no appointments', function () {
        $patient = Patient::factory()->create();
        appointmentStartingAt('2026-09-03 09:00:00')->create();

        $this->getJson(route('appointments.index', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    test('returns 422 when the patient id does not exist', function () {
        $this->getJson(route('appointments.index', ['patient_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'patient_id' => 'The selected patient id is invalid.',
            ]);
    });

    test('filters appointments by doctor', function () {
        $doctor = Doctor::factory()->create();
        $otherDoctor = Doctor::factory()->create();

        $appointment = appointmentStartingAt('2026-09-03 09:00:00')->for($doctor)->create();
        appointmentStartingAt('2026-09-03 10:00:00')->for($otherDoctor)->create();

        $this->getJson(route('appointments.index', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertJsonPath('data', [
                appointmentResourceMap($appointment),
            ]);
    });

    test('returns the first page of appointments', function () {
        $start = CarbonImmutable::createFromFormat(ApiDateTime::FORMAT, '2026-09-03 08:00:00');

        Appointment::factory()
            ->count(16)
            ->sequence(fn ($sequence) => [
                'start_time' => $start->addMinutes($sequence->index * 30),
                'end_time' => $start->addMinutes($sequence->index * 30 + 30),
            ])
            ->create();

        $this->getJson(route('appointments.index'))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('data.0.start_time', '2026-09-03 08:00:00')
            ->assertJsonPath('data.14.start_time', '2026-09-03 15:00:00');
    });

    test('returns the second page of appointments', function () {
        $start = CarbonImmutable::createFromFormat(ApiDateTime::FORMAT, '2026-09-03 08:00:00');

        Appointment::factory()
            ->count(16)
            ->sequence(fn ($sequence) => [
                'start_time' => $start->addMinutes($sequence->index * 30),
                'end_time' => $start->addMinutes($sequence->index * 30 + 30),
            ])
            ->create();

        $this->getJson(route('appointments.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.start_time', '2026-09-03 15:30:00');
    });

    test('returns 422 when the status filter is invalid', function () {
        $this->getJson(route('appointments.index', ['status' => 'unknown']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status' => 'The selected status is invalid.',
            ]);
    });
});

describe('store', function () {
    test('creates a pending appointment for a free slot', function () {
        $doctor = createBookableDoctor();
        $patient = Patient::factory()->create();
        $payload = validAppointmentPayload($doctor, $patient);

        $response = $this->postJson(route('appointments.store'), $payload);

        $appointment = Appointment::query()->sole();

        $response
            ->assertCreated()
            ->assertExactJson([
                'data' => appointmentResourceMap($appointment),
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
            'status' => AppointmentStatus::Pending->value,
            'cancellation_reason' => null,
        ]);
    });

    test('returns 422 when the slot is already booked by a :dataset appointment', function (string $state) {
        $doctor = createBookableDoctor();
        $patient = Patient::factory()->create();

        Appointment::factory()->{$state}()->create([
            'doctor_id' => $doctor->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
        ]);

        $this->postJson(route('appointments.store'), validAppointmentPayload($doctor, $patient))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'start_time' => 'The slot is not available.',
            ]);

        $this->assertDatabaseCount('appointments', 1);
    })->with([
        'pending' => 'pending',
        'confirmed' => 'confirmed',
    ]);

    test('returns 422 when the doctor has no availability', function () {
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();

        $this->postJson(route('appointments.store'), validAppointmentPayload($doctor, $patient))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'start_time' => 'The slot is not available.',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    });

    test('returns 422 when the start time is not on a slot boundary', function () {
        $doctor = createBookableDoctor();
        $patient = Patient::factory()->create();

        $this->postJson(route('appointments.store'), validAppointmentPayload($doctor, $patient, [
            'start_time' => '2026-09-03 09:15:00',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'start_time' => 'The slot is not available.',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    });

    test('returns 422 when the start time is in the past', function () {
        $doctor = createBookableDoctor();
        $patient = Patient::factory()->create();

        $this->postJson(route('appointments.store'), validAppointmentPayload($doctor, $patient, [
            'start_time' => '2026-09-01 09:00:00',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'start_time' => 'The start time field must be a date after now.',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    });

    test('returns 422 when the patient already has an overlapping appointment', function () {
        $patient = Patient::factory()->create();
        $firstDoctor = createBookableDoctor();

        Appointment::factory()->pending()->create([
            'doctor_id' => $firstDoctor->id,
            'patient_id' => $patient->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
        ]);

        $secondDoctor = createBookableDoctor([
            'starts_at' => '2026-09-03 09:15:00',
            'ends_at' => '2026-09-03 10:15:00',
        ]);

        $this->postJson(route('appointments.store'), validAppointmentPayload($secondDoctor, $patient, [
            'start_time' => '2026-09-03 09:15:00',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'start_time' => 'The patient has an overlapping appointment.',
            ]);

        $this->assertDatabaseCount('appointments', 1);
    });

    test('creates an appointment when a cancelled booking left the slot free', function () {
        $doctor = createBookableDoctor();
        $patient = Patient::factory()->create();

        Appointment::factory()->cancelled()->create([
            'doctor_id' => $doctor->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
        ]);

        $this->postJson(route('appointments.store'), validAppointmentPayload($doctor, $patient))
            ->assertCreated();

        $this->assertDatabaseCount('appointments', 2);
        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'start_time' => '2026-09-03 09:00:00',
            'end_time' => '2026-09-03 09:30:00',
            'status' => AppointmentStatus::Pending->value,
        ]);
    });

    test('returns 422 when the doctor does not exist', function () {
        $patient = Patient::factory()->create();

        $this->postJson(route('appointments.store'), [
            'doctor_id' => 999,
            'patient_id' => $patient->id,
            'start_time' => '2026-09-03 09:00:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'doctor_id' => 'The selected doctor id is invalid.',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    });

    test('returns 422 when the patient does not exist', function () {
        $doctor = createBookableDoctor();

        $this->postJson(route('appointments.store'), [
            'doctor_id' => $doctor->id,
            'patient_id' => 999,
            'start_time' => '2026-09-03 09:00:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'patient_id' => 'The selected patient id is invalid.',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    });

    test('returns 422 when a validation rule fails', function (array $payload, array $errors) {
        $this->postJson(route('appointments.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseCount('appointments', 0);
    })->with('invalid appointment payloads');
});

describe('confirm', function () {
    test('confirms a pending appointment and returns the resource map', function () {
        $appointment = Appointment::factory()->pending()->create();

        $response = $this->postJson(route('appointments.confirm', $appointment));

        $appointment->refresh();

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => appointmentResourceMap($appointment),
            ]);

        expect($appointment->status)->toBe(AppointmentStatus::Confirmed);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    });

    test('returns 409 when confirming a :dataset appointment', function (Appointment $appointment) {
        $status = $appointment->status;

        $this->postJson(route('appointments.confirm', $appointment))
            ->assertConflict()
            ->assertJsonPath('message', 'Invalid appointment status transition.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => $status->value,
        ]);
    })->with([
        'confirmed' => fn () => Appointment::factory()->confirmed()->create(),
        'cancelled' => fn () => Appointment::factory()->cancelled()->create(),
        'completed' => fn () => Appointment::factory()->completed()->create(),
    ]);

    test('returns 404 when the appointment does not exist', function () {
        $this->postJson(route('appointments.confirm', 1))
            ->assertNotFound();
    });
});

describe('complete', function () {
    test('completes a confirmed appointment and returns the resource map', function () {
        $appointment = Appointment::factory()->confirmed()->create();

        $response = $this->postJson(route('appointments.complete', $appointment));

        $appointment->refresh();

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => appointmentResourceMap($appointment),
            ]);

        expect($appointment->status)->toBe(AppointmentStatus::Completed);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Completed->value,
        ]);
    });

    test('returns 409 when completing a :dataset appointment', function (Appointment $appointment) {
        $status = $appointment->status;

        $this->postJson(route('appointments.complete', $appointment))
            ->assertConflict()
            ->assertJsonPath('message', 'Invalid appointment status transition.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => $status->value,
        ]);
    })->with([
        'pending' => fn () => Appointment::factory()->pending()->create(),
        'cancelled' => fn () => Appointment::factory()->cancelled()->create(),
        'completed' => fn () => Appointment::factory()->completed()->create(),
    ]);

    test('returns 404 when the appointment does not exist', function () {
        $this->postJson(route('appointments.complete', 1))
            ->assertNotFound();
    });
});

describe('cancel', function () {
    test('cancels a pending appointment when the start time is within 24 hours', function () {
        $appointment = appointmentStartingAt('2026-09-03 11:00:00')->pending()->create();

        $response = $this->postJson(route('appointments.cancel', $appointment), validCancelPayload());

        $appointment->refresh();

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => appointmentResourceMap($appointment),
            ]);

        expect($appointment->status)->toBe(AppointmentStatus::Cancelled);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
            'cancellation_reason' => 'Patient is unavailable.',
        ]);
    });

    test('cancels a confirmed appointment when the start time is :dataset', function (string $startTime) {
        $appointment = appointmentStartingAt($startTime)->confirmed()->create();

        $response = $this->postJson(route('appointments.cancel', $appointment), validCancelPayload());

        $appointment->refresh();

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => appointmentResourceMap($appointment),
            ]);

        expect($appointment->status)->toBe(AppointmentStatus::Cancelled);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
            'cancellation_reason' => 'Patient is unavailable.',
        ]);
    })->with([
        'exactly 24 hours away' => '2026-09-03 12:00:00',
        'more than 24 hours away' => '2026-09-04 09:00:00',
    ]);

    test('returns 409 when cancelling a confirmed appointment less than 24 hours before the start time', function () {
        $appointment = appointmentStartingAt('2026-09-03 11:59:59')->confirmed()->create();

        $this->postJson(route('appointments.cancel', $appointment), validCancelPayload())
            ->assertConflict()
            ->assertJsonPath('message', 'Appointments can only be cancelled at least 24 hours before the start.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    });

    test('returns 409 when cancelling a :dataset appointment', function (Appointment $appointment) {
        $status = $appointment->status;

        $this->postJson(route('appointments.cancel', $appointment), validCancelPayload())
            ->assertConflict()
            ->assertJsonPath('message', 'Invalid appointment status transition.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => $status->value,
        ]);
    })->with([
        'cancelled' => fn () => Appointment::factory()->cancelled()->create(),
        'completed' => fn () => Appointment::factory()->completed()->create(),
    ]);

    test('returns 422 when the cancellation payload is invalid', function (array $payload, array $errors) {
        $appointment = Appointment::factory()->pending()->create();

        $this->postJson(route('appointments.cancel', $appointment), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Pending->value,
            'cancellation_reason' => null,
        ]);
    })->with('invalid cancel payloads');

    test('returns 404 when the appointment does not exist', function () {
        $this->postJson(route('appointments.cancel', 1))
            ->assertNotFound();
    });
});
