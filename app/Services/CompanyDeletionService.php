<?php

namespace App\Services;

use App\Models\Address;
use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\Info;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Removes a company and everything that hangs off it.
 *
 * Used both by `php artisan company:delete` and by the approval of a CIF
 * removal request, so the two can never drift apart.
 */
final class CompanyDeletionService
{
    /**
     * @return array{companies: int, addresses: int, infos: int, statuses: int, caen_companies: int, legal_representatives: int, natural_person_representatives: int, e_u_branches: int}
     */
    public static function deleteByCui(string $cui): array
    {
        // A CIF can belong to more than one company (an old, deregistered one
        // and a newly registered one), so every row for it has to go.
        $companies = Company::where('cui', $cui)->get();

        if ($companies->isEmpty()) {
            return self::emptySummary();
        }

        $summary = self::emptySummary();

        DB::transaction(function () use ($companies, &$summary): void {
            foreach ($companies as $company) {
                $related = self::deleteRelated($company);

                foreach ($related as $key => $count) {
                    $summary[$key] += $count;
                }

                $company->delete();
                $summary['companies']++;
            }
        });

        self::forgetCaches($cui);

        return $summary;
    }

    public static function forgetCaches(string $cui): void
    {
        Cache::forget("company_page_{$cui}");
        Cache::forget("company_api_{$cui}");
    }

    /**
     * @return array{addresses: int, infos: int, statuses: int, caen_companies: int, legal_representatives: int, natural_person_representatives: int, e_u_branches: int}
     */
    private static function deleteRelated(Company $company): array
    {
        // Tables with ON DELETE RESTRICT - must delete manually
        $addresses = (int) Address::where('company_id', $company->id)->delete();
        $infos = (int) Info::where('company_id', $company->id)->delete();

        // Tables linked by reg_com (no FK or FK with CASCADE)
        $statuses = (int) Status::where('registration', $company->reg_com)->delete();
        $caen = (int) CaenCompany::where('registration', $company->reg_com)->delete();
        $legalRepresentatives = (int) LegalRepresentative::where('registration', $company->reg_com)->delete();
        $naturalPersonRepresentatives = (int) NaturalPersonRepresentative::where('registration', $company->reg_com)->delete();
        $euBranches = (int) EUBranch::where('registration', $company->reg_com)->delete();

        return [
            'addresses' => $addresses,
            'infos' => $infos,
            'statuses' => $statuses,
            'caen_companies' => $caen,
            'legal_representatives' => $legalRepresentatives,
            'natural_person_representatives' => $naturalPersonRepresentatives,
            'e_u_branches' => $euBranches,
        ];
    }

    /**
     * @return array{companies: int, addresses: int, infos: int, statuses: int, caen_companies: int, legal_representatives: int, natural_person_representatives: int, e_u_branches: int}
     */
    private static function emptySummary(): array
    {
        return [
            'companies' => 0,
            'addresses' => 0,
            'infos' => 0,
            'statuses' => 0,
            'caen_companies' => 0,
            'legal_representatives' => 0,
            'natural_person_representatives' => 0,
            'e_u_branches' => 0,
        ];
    }
}
