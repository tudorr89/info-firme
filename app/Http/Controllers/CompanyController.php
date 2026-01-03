<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    public function show(string $cui)
    {
        // Validate CUI format (numeric only)
        if (! ctype_digit($cui)) {
            abort(404);
        }

        // Cache company data for 24 hours
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
                ])->where('cui', $cui)->firstOrFail();
            }
        );

        return view('companies.show', compact('company'));
    }
}
