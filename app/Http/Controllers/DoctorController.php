<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doctor\IndexDoctorRequest;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorCollection;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DoctorController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function index(IndexDoctorRequest $request)
    {
        return new DoctorCollection(
            Doctor::query()->simplePaginate()
        );
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        return Doctor::create($request->validated())
            ->toResource()
            ->response()
            ->setStatusCode(201);
    }

    public function show(Doctor $doctor)
    {
        return $doctor->toResource();
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $doctor->update($request->validated());

        return $doctor->toResource();
    }

    public function destroy(Doctor $doctor): Response
    {
        $doctor->delete();

        return response()->noContent();
    }
}
