<?php

namespace App\Http\Controllers\Api;

use App\Enums\MistakeSeverity;
use App\Enums\MistakeType;
use App\Http\Controllers\Controller;
use App\Http\Resources\MistakeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MistakeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => [
                'sometimes',
                'string',
                'in:grammar,vocabulary,spelling,word_order',
            ],
            'subtype' => [
                'sometimes',
                'string',
            ],
            'severity' => [
                'sometimes',
                'string',
                'in:low,medium,high',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $mistakes = $request->user()
            ->mistakes()
            ->with('message.mistakes')
            ->latest()
            ->when(
                isset($validated['type']),
                fn ($query) => $query->where(
                    'type',
                    $validated['type']
                )
            )
            ->when(
                isset($validated['subtype']),
                fn ($query) => $query->where(
                    'subtype',
                    $validated['subtype']
                )
            )
            ->when(
                isset($validated['severity']),
                fn ($query) => $query->where(
                    'severity',
                    $validated['severity']
                )
            )
            ->paginate($validated['per_page'] ?? 20);

        return MistakeResource::collection($mistakes);
    }
}