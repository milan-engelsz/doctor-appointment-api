<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\Availability\IndexAvailabilityRequest;
use App\Http\Requests\Doctor\Availability\StoreAvailabilityRequest;
use App\Http\Resources\AvailabilityCollection;
use App\Models\Doctor;
use Dedoc\Scramble\Attributes\Group;

#[Group('Doctor Availability')]
class AvailabilityController extends Controller
{
    public function index(IndexAvailabilityRequest $request, Doctor $doctor)
    {
        return new AvailabilityCollection(
            $doctor->availabilities()->simplePaginate()
        );
    }

    public function store(StoreAvailabilityRequest $request, Doctor $doctor)
    {
        return $doctor->availabilities()
            ->create($request->validated())
            ->toResource()
            ->response()
            ->setStatusCode(201);
    }
}
