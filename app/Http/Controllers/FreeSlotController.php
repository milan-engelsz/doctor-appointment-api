<?php

namespace App\Http\Controllers;

use App\Http\Requests\FreeSlot\IndexFreeSlotRequest;
use App\Http\Resources\FreeSlotCollection;
use App\Services\FreeSlotService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;

class FreeSlotController extends Controller
{
    public function index(IndexFreeSlotRequest $request, FreeSlotService $service)
    {
        $page = Paginator::resolveCurrentPage();
        $perPage = 15;
        $slots = $service->getFreeSlots(
            doctorId: $request->validated('doctor_id'),
            from: $request->filled('from') ? CarbonImmutable::parse($request->validated('from')) : null,
            to: $request->filled('to') ? CarbonImmutable::parse($request->validated('to')) : null,
        );

        return new FreeSlotCollection(
            new Paginator(
                $slots->forPage($page, $perPage)->values(),
                $perPage,
                $page,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ]
            )
        );
    }
}
