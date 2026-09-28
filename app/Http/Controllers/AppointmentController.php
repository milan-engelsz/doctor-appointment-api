<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\ChangeAppointmentStatusAction;
use App\Actions\Appointment\StoreAppointmentAction;
use App\Enums\AppointmentStatus;
use App\Exceptions\InvalidAppointmentTransition;
use App\Http\Requests\Appointment\CancelAppointmentRequest;
use App\Http\Requests\Appointment\IndexAppointmentRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentCollection;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;

class AppointmentController extends Controller
{
    public function index(IndexAppointmentRequest $request)
    {
        return new AppointmentCollection(
            Appointment::query()
                ->ordered()
                ->when($request->input('doctor_id'), function ($query, $doctorId) {
                    $query->where('doctor_id', $doctorId);
                })
                ->when($request->input('patient_id'), function ($query, $patientId) {
                    $query->where('patient_id', $patientId);
                })
                ->when($request->input('status'), function ($query, $status) {
                    $query->where('status', $status);
                })
                ->simplePaginate()
        );
    }

    public function store(StoreAppointmentRequest $request, StoreAppointmentAction $action)
    {
        $appointment = $action->execute(
            doctor: Doctor::query()->find($request->input('doctor_id')),
            patient: Patient::query()->find($request->input('patient_id')),
            startTime: $request->startTime(),
        );

        return $appointment
            ->toResource()
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @throws \Throwable
     * @throws InvalidAppointmentTransition
     */
    public function cancel(CancelAppointmentRequest $request, Appointment $appointment, CancelAppointmentAction $action)
    {
        $action->execute($appointment, $request->validated('cancellation_reason'));

        return $appointment
            ->toResource()
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @throws \Throwable
     * @throws InvalidAppointmentTransition
     */
    public function confirm(Appointment $appointment, ChangeAppointmentStatusAction $action)
    {
        $action->execute($appointment, AppointmentStatus::Confirmed);

        return $appointment
            ->toResource()
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @throws \Throwable
     * @throws InvalidAppointmentTransition
     */
    public function complete(Appointment $appointment, ChangeAppointmentStatusAction $action)
    {
        $action->execute($appointment, AppointmentStatus::Completed);

        return $appointment
            ->toResource()
            ->response()
            ->setStatusCode(200);
    }
}
