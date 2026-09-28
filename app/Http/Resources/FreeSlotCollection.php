<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FreeSlotCollection extends ResourceCollection
{
    public $collects = FreeSlotResource::class;
}
