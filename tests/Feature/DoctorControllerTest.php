<?php

use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

function doctorResourceMap(Doctor $doctor): array
{
    return [
        'id' => $doctor->id,
        'name' => $doctor->name,
        'email' => $doctor->email,
        'field_of_expertise' => $doctor->field_of_expertise,
    ];
}

function validDoctorPayload(array $overrides = []): array
{
    return [
        'name' => 'MD. Jane Doe',
        'email' => 'jane.doe@example.com',
        'field_of_expertise' => 'Cardiology',
        ...$overrides,
    ];
}

dataset('invalid doctor payloads', function () {
    $valid = validDoctorPayload();

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
        'field of expertise is required' => [
            Arr::except($valid, 'field_of_expertise'),
            ['field_of_expertise' => 'The field of expertise field is required.'],
        ],
        'field of expertise must be a string' => [
            [...$valid, 'field_of_expertise' => ['Cardiology']],
            ['field_of_expertise' => 'The field of expertise field must be a string.'],
        ],
        'field of expertise may not be greater than 255 characters' => [
            [...$valid, 'field_of_expertise' => str_repeat('a', 256)],
            ['field_of_expertise' => 'The field of expertise field must not be greater than 255 characters.'],
        ],
        'all fields are required' => [
            [],
            [
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'field_of_expertise' => 'The field of expertise field is required.',
            ],
        ],
    ];
});

describe('index', function () {
    test('return first 15 doctors', function () {
        $total = 16;

        Doctor::factory()->count($total)->create();

        $response = $this->getJson(route('doctors.index'));

        $response
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', 1)
            ->assertJsonPath('meta.to', 15);
    });

    test('returns the empty doctor list', function () {
        $response = $this->getJson(route('doctors.index'));

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
        $this->postJson(route('doctors.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseCount('doctors', 0);
    })->with('invalid doctor payloads');

    test('returns 422 when the email is already taken', function () {
        $existing = Doctor::factory()->create();

        $this->postJson(route('doctors.store'), validDoctorPayload([
            'email' => $existing->email,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseCount('doctors', 1);
    });

    test('creates a doctor and returns the resource map', function () {
        $payload = validDoctorPayload();

        $response = $this->postJson(route('doctors.store'), $payload);

        $doctor = Doctor::query()->sole();

        $response
            ->assertCreated()
            ->assertExactJson([
                'data' => doctorResourceMap($doctor),
            ]);

        $this->assertDatabaseHas('doctors', $payload);
    });

    test('does not persist unexpected payload keys', function () {
        $this->travelTo('2026-09-22 12:00:00');

        $payload = [
            ...validDoctorPayload(),
            'id' => 999,
            'created_at' => '2020-01-01 00:00:00',
            'unknown_field' => 'ignored',
        ];

        $this->postJson(route('doctors.store'), $payload)
            ->assertCreated();

        $doctor = Doctor::query()->sole();

        expect($doctor->id)->not->toBe(999);
        expect($doctor->created_at?->toDateTimeString())->toBe('2026-09-22 12:00:00');
        expect($doctor->getAttributes())->not->toHaveKey('unknown_field');
        $this->assertDatabaseHas('doctors', validDoctorPayload());
    });

});

describe('show', function () {
    test('returns the resource map when the doctor exists', function () {
        $doctor = Doctor::factory()->create();

        $this->getJson(route('doctors.show', $doctor))
            ->assertOk()
            ->assertExactJson([
                'data' => doctorResourceMap($doctor),
            ]);
    });

    test('returns 404 when the doctor does not exist', function () {
        $this->getJson(route('doctors.show', ['doctor' => 1]))
            ->assertNotFound();
    });
});

describe('update', function () {
    test('rejects the payload when a validation rule fails', function (array $payload, array $errors) {
        $doctor = Doctor::factory()->create();

        $this->putJson(route('doctors.update', $doctor), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errors);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'name' => $doctor->name,
            'email' => $doctor->email,
            'field_of_expertise' => $doctor->field_of_expertise,
        ]);
    })->with('invalid doctor payloads');

    test('returns 422 when the email is already taken', function () {
        $other = Doctor::factory()->create();
        $doctor = Doctor::factory()->create();

        $this->putJson(route('doctors.update', $doctor), validDoctorPayload([
            'email' => $other->email,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'email' => $doctor->email,
        ]);
    });

    test('updates a doctor and returns the resource map', function (Doctor $doctor, array $payload) {
        $response = $this->putJson(route('doctors.update', $doctor), $payload);

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => doctorResourceMap($doctor->refresh()),
            ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            ...$payload,
        ]);
    })->with([
        'all attributes change' => function (): array {
            $doctor = Doctor::factory()->create();

            return [
                $doctor,
                validDoctorPayload([
                    'name' => str_repeat('a', 255),
                    'email' => 'updated.doctor@example.com',
                    'field_of_expertise' => 'updated FoE',
                ]),
            ];
        },
        'name changes and email stays the same' => function (): array {
            $doctor = Doctor::factory()->create();

            return [
                $doctor,
                [
                    'name' => 'Updated Name',
                    'email' => $doctor->email,
                    'field_of_expertise' => $doctor->field_of_expertise,
                ],
            ];
        },
    ]);

    test('returns 404 when the doctor does not exist', function () {
        $doctor = Doctor::factory()->create();

        $this->putJson(route('doctors.update', $doctor->id + 1), validDoctorPayload())
            ->assertNotFound();

        $this->assertModelExists($doctor);
    });

    test('does not persist unexpected payload keys', function () {
        $doctor = Doctor::factory()->create();
        $originalId = $doctor->id;
        $originalCreatedAt = $doctor->created_at?->toJSON();

        $payload = [
            ...validDoctorPayload([
                'email' => 'updated.doctor@example.com',
            ]),
            'id' => $originalId + 50,
            'created_at' => '2020-01-01T00:00:00.000000Z',
            'unknown_field' => 'ignored',
        ];

        $this->putJson(route('doctors.update', $doctor), $payload)
            ->assertOk();

        $doctor->refresh();

        expect($doctor->id)->toBe($originalId);
        expect($doctor->created_at?->toJSON())->toBe($originalCreatedAt);
        expect($doctor->getAttributes())->not->toHaveKey('unknown_field');
        $this->assertDatabaseHas('doctors', [
            'id' => $originalId,
            'name' => 'MD. Jane Doe',
            'email' => 'updated.doctor@example.com',
            'field_of_expertise' => 'Cardiology',
        ]);
    });

    describe('destroy', function () {
        test('deletes the doctor when the entity exists', function () {
            $doctor = Doctor::factory()->create();

            $this->deleteJson(route('doctors.destroy', $doctor))
                ->assertNoContent();

            $this->assertModelMissing($doctor);
        });

        test('returns 404 when the doctor does not exist', function () {
            $doctor = Doctor::factory()->create();

            $this->deleteJson(route('doctors.destroy', $doctor->id + 1))
                ->assertNotFound();

            $this->assertModelExists($doctor);
        });
    });
});
