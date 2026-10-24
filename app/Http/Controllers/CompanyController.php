<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\BannedCifService;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    public function show(string $cui)
    {
        // Validate CUI format (numeric only)
        if (! ctype_digit($cui)) {
            abort(404);
        }

        // Banned CIFs are skipped at import time; a row that was imported
        // before the ban is enforced must not be published either.
        if (BannedCifService::isBanned($cui)) {
            abort(404);
        }

        // A CIF can belong to more than one company (the old, deregistered one
        // and a newly registered one), so the still operating company wins.
        $company = Cache::remember(
            "company_page_{$cui}",
            now()->addDay(),
            function () use ($cui) {
                return Company::with([
                    'address',
                    'info',
                    'status.details',
                    'caen.details',
                    'legalRepresentatives',
                    'naturalPersonRepresentatives',
                    'euBranches',
                ])
                    ->where('cui', $cui)
                    ->preferActive()
                    ->orderByDesc('registration_date')
                    ->firstOrFail();
            }
        );

        return view('companies.show', compact('company'));
    }
}
