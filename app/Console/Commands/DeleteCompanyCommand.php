<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CompanyDeletionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('company:delete {cif : The CIF/CUI of the company to delete} {--force : Skip the confirmation prompt}')]
#[Description('Delete a company and all its related data by CIF/CUI')]
class DeleteCompanyCommand extends Command
{
    public function handle(): int
    {
        $cif = $this->argument('cif');

        // A CIF can belong to more than one company (an old, deregistered one
        // and a newly registered one), so every row for it has to go.
        $companies = Company::where('cui', $cif)->get();

        if ($companies->isEmpty()) {
            $this->error("Company with CIF '{$cif}' not found.");

            return self::FAILURE;
        }

        foreach ($companies as $company) {
            $this->info("Found company: {$company->name} (CUI: {$company->cui}, reg_com: {$company->reg_com})");
        }

        if (! $this->option('force')
            && ! $this->confirm('Are you sure you want to delete this company and all its related data?', false)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $deleted = CompanyDeletionService::deleteByCui((string) $cif);

        $this->info("Deleted {$deleted['companies']} company record(s) for CUI '{$cif}' and all related data.");
        $this->line('Deleted related records:');
        $this->line("  - addresses: {$deleted['addresses']}");
        $this->line("  - infos: {$deleted['infos']}");
        $this->line("  - statuses: {$deleted['statuses']}");
        $this->line("  - caen_companies: {$deleted['caen_companies']}");
        $this->line("  - legal_representatives: {$deleted['legal_representatives']}");
        $this->line("  - natural_person_representatives: {$deleted['natural_person_representatives']}");
        $this->line("  - e_u_branches: {$deleted['e_u_branches']}");

        return self::SUCCESS;
    }
}
