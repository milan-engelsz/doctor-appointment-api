<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patient\IndexPatientRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientCollection;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index(IndexPatientRequest $request)
    {
        return new PatientCollection(
            Patient::query()->simplePaginate()
        );
    }

    public function store(StorePatientRequest $request)
    {
        return Patient::create($request->validated())
            ->toResource()
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient)
    {
        return $patient->toResource();
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient->update($request->validated());

        return $patient->toResource();
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->noContent();
    }
}
