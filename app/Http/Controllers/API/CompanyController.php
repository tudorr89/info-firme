<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    /**
     * Relations returned for a single company lookup.
     *
     * @var array<int, string>
     */
    private const COMPANY_RELATIONS = [
        'address',
        'info',
        'status.details',
        'caen.details',
        'caen',
        'legalRepresentatives',
        'naturalPersonRepresentatives',
        'euBranches',
    ];

    public function __invoke(CompanyRequest $request): JsonResponse
    {
        if ($request->cui) {
            return response()->json($this->forCui((string) $request->cui));
        }

        return response()->json($this->forName((string) $request->company));
    }

    /**
     * A CIF can belong to more than one company (an old, deregistered company
     * and a newly registered one), so the still operating company is returned.
     *
     * Arrays are cached instead of models: unserializing Eloquent models needs
     * them to be allowlisted and breaks whenever the model changes.
     *
     * @return array<string, mixed>
     */
    private function forCui(string $cui): array
    {
        return Cache::remember("company_api_{$cui}", now()->addDay(), function () use ($cui) {
            return Company::with(self::COMPANY_RELATIONS)
                ->where('cui', $cui)
                ->preferActive()
                ->orderByDesc('registration_date')
                ->firstOrFail()
                ->toArray();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function forName(string $name): array
    {
        return Cache::remember('company_api_name_'.md5($name), now()->addDay(), function () use ($name) {
            return Company::with(self::COMPANY_RELATIONS)
                ->presentable()
                ->where('name', 'LIKE', $name.'%')
                ->orderByDesc('registration_date')
                ->paginate()
                ->toArray();
        });
    }
}
