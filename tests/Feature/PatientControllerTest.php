<?php

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

function patientResourceMap(Patient $patient): array
{
    return [
        'id' => $patient->id,
        'name' => $patient->name,
        'email' => $patient->email,
        'phone' => $patient->phone,
    ];
}

function validPatientPayload(array $overrides = []): array
{
    return [
        'name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
        'phone' => '0036301234567',
        ...$overrides,
    ];
}

dataset('invalid patient payloads', function () {
    $valid = validPatientPayload();

    return [
        'name is required' => [
            Arr::except($valid, 'name'),
            ['name' => 'The name field is required.'],
        ],
        'name must be a string' => [
            [...$valid, 'name' => ['Jane']],
            ['name' => 'The name field must be a string.'],
        ],
        'name may not be greater than 255 characters' => [
            [...$valid, 'name' => str_repeat('a', 256)],
            ['name' => 'The name field must not be greater than 255 characters.'],
        ],
        'email is required' => [
            Arr::except($valid, 'email'),
            ['email' => 'The email field is required.'],
        ],
        'email must be a valid email address' => [
            [...$valid, 'email' => 'not-an-email'],
            ['email' => 'The email field must be a valid email address.'],
        ],
        'email may not be greater than 255 characters' => [
            [...$valid, 'email' => str_repeat('a', 244).'@example.com'],
            ['email' => 'The email field must not be greater than 255 characters.'],
        ],
        'phone is required' => [
            Arr::except($valid, 'phone'),
            ['phone' => 'The phone field is required.'],
        ],
        'phone must be a string' => [
            [...$valid, 'phone' => ['003021234567']],
            ['phone' => 'The phone field must be a string.'],
        ],
        'phone may not be greater than 20 characters' => [
            [...$valid, 'phone' => str_repeat('a', 22)],
            ['phone' => 'The phone field must not be greater than 20 characters.'],
        ],
        'all fields are required' => [
            [],
            [
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'phone' => 'The phone field is required.',
            ],
        ],
    ];
});

describe('index', function () {
    test('return first 15 patients', function () {
        $total = 16;

        Patient::factory()->count($total)->create();

        $response = $this->getJson(route('patients.index'));

        $response
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', 1)
            ->assertJsonPath('meta.to', 15)
            ->assertJsonPath('meta.to', 15);
    });

    test('returns the empty patient list', function () {
        $response = $this->getJson(route('patients.index'));

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', null)
            ->assertJsonPath('meta.to', null);
    });
});

describe('store', function () {

    test('rejects the payload when a validation rule fails', function (array $payload, array $errors) {
        $this->postJson(route('patients.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseCount('patients', 0);
    })->with('invalid patient payloads');

    test('returns 422 when the email is already taken', function () {
        $existing = Patient::factory()->create();

        $this->postJson(route('patients.store'), validPatientPayload([
            'email' => $existing->email,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseCount('patients', 1);
    });

    test('creates a patient and returns the resource map', function () {
        $payload = validPatientPayload();

        $response = $this->postJson(route('patients.store'), $payload);

        $patient = Patient::query()->sole();

        $response
            ->assertCreated()
            ->assertExactJson([
                'data' => patientResourceMap($patient),
            ]);

        $this->assertDatabaseHas('patients', $payload);
    });

    test('does not persist unexpected payload keys', function () {
        $this->travelTo('2026-09-22 12:00:00');

        $payload = [
            ...validPatientPayload(),
            'id' => 999,
            'created_at' => '2020-01-01 00:00:00',
            'unknown_field' => 'ignored',
        ];

        $this->postJson(route('patients.store'), $payload)
            ->assertCreated();

        $patient = Patient::query()->sole();

        expect($patient->id)->not->toBe(999);
        expect($patient->created_at?->toDateTimeString())->toBe('2026-09-22 12:00:00');
        expect($patient->getAttributes())->not->toHaveKey('unknown_field');
        $this->assertDatabaseHas('patients', validPatientPayload());
    });

});

describe('show', function () {
    test('returns the resource map when the patient exists', function () {
        $patient = Patient::factory()->create();

        $this->getJson(route('patients.show', $patient))
            ->assertOk()
            ->assertExactJson([
                'data' => patientResourceMap($patient),
            ]);
    });

    test('returns 404 when the patient does not exist', function () {
        $this->getJson(route('patients.show', ['patient' => 1]))
            ->assertNotFound();
    });
});

describe('update', function () {
    test('rejects the payload when a validation rule fails', function (array $payload, array $errors) {
        $patient = Patient::factory()->create();

        $this->putJson(route('patients.update', $patient), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => $patient->name,
            'email' => $patient->email,
            'phone' => $patient->phone,
        ]);
    })->with('invalid patient payloads');

    test('returns 422 when the email is already taken', function () {
        $other = Patient::factory()->create();
        $patient = Patient::factory()->create();

        $this->putJson(route('patients.update', $patient), validPatientPayload([
            'email' => $other->email,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'email' => $patient->email,
        ]);
    });

    test('updates a patient and returns the resource map', function (Patient $patient, array $payload) {
        $response = $this->putJson(route('patients.update', $patient), $payload);

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => patientResourceMap($patient->refresh()),
            ]);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            ...$payload,
        ]);
    })->with([
        'all attributes change' => function (): array {
            $patient = Patient::factory()->create();

            return [
                $patient,
                validPatientPayload([
                    'name' => str_repeat('a', 255),
                    'email' => 'updated.patient@example.com',
                    'phone' => '00707654321',
                ]),
            ];
        },
        'name changes and email stays the same' => function (): array {
            $patient = Patient::factory()->create();

            return [
                $patient,
                [
                    'name' => 'Updated Name',
                    'email' => $patient->email,
                    'phone' => $patient->phone,
                ],
            ];
        },
    ]);

    test('returns 404 when the patient does not exist', function () {
        $patient = Patient::factory()->create();

        $this->putJson(route('patients.update', $patient->id + 1), validPatientPayload())
            ->assertNotFound();

        $this->assertModelExists($patient);
    });

    test('does not persist unexpected payload keys', function () {
        $patient = Patient::factory()->create();
        $originalId = $patient->id;
        $originalCreatedAt = $patient->created_at?->toJSON();

        $payload = [
            ...validPatientPayload([
                'email' => 'updated.patient@example.com',
            ]),
            'id' => $originalId + 50,
            'created_at' => '2020-01-01T00:00:00.000000Z',
            'unknown_field' => 'ignored',
        ];

        $this->putJson(route('patients.update', $patient), $payload)
            ->assertOk();

        $patient->refresh();

        expect($patient->id)->toBe($originalId);
        expect($patient->created_at?->toJSON())->toBe($originalCreatedAt);
        expect($patient->getAttributes())->not->toHaveKey('unknown_field');
        $this->assertDatabaseHas('patients', [
            'id' => $originalId,
            'name' => 'Jane Doe',
            'email' => 'updated.patient@example.com',
            'phone' => '0036301234567',
        ]);
    });

    describe('destroy', function () {
        test('deletes the patient when the entity exists', function () {
            $patient = Patient::factory()->create();

            $this->deleteJson(route('patients.destroy', $patient))
                ->assertNoContent();

            $this->assertModelMissing($patient);
        });

        test('returns 404 when the patient does not exist', function () {
            $patient = Patient::factory()->create();

            $this->deleteJson(route('patients.destroy', $patient->id + 1))
                ->assertNotFound();

            $this->assertModelExists($patient);
        });
    });
});
