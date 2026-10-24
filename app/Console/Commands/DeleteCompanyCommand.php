<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\Info;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Models\Status;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('company:delete {cif : The CIF/CUI of the company to delete} {--force : Skip the confirmation prompt}')]
#[Description('Delete a company and all its related data by CIF/CUI')]
class DeleteCompanyCommand extends Command
{
    public function handle(): int
    {
        $cif = $this->argument('cif');

        $company = Company::where('cui', $cif)->first();

        if (! $company) {
            $this->error("Company with CIF '{$cif}' not found.");

            return self::FAILURE;
        }

        $this->info("Found company: {$company->name} (CUI: {$company->cui}, reg_com: {$company->reg_com})");

        if (! $this->option('force')
            && ! $this->confirm('Are you sure you want to delete this company and all its related data?', false)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($company) {
            $this->deleteRelated($company);
            $company->delete();
        });

        $this->info("Company '{$company->name}' and all related data have been deleted.");

        return self::SUCCESS;
    }

    private function deleteRelated(Company $company): void
    {
        // Tables with ON DELETE RESTRICT - must delete manually
        $deletedAddresses = Address::where('company_id', $company->id)->delete();
        $deletedInfo = Info::where('company_id', $company->id)->delete();

        // Tables linked by reg_com (no FK or FK with CASCADE)
        $deletedStatuses = Status::where('registration', $company->reg_com)->delete();
        $deletedCaen = CaenCompany::where('registration', $company->reg_com)->delete();
        $deletedLegalReps = LegalRepresentative::where('registration', $company->reg_com)->delete();
        $deletedNaturalReps = NaturalPersonRepresentative::where('registration', $company->reg_com)->delete();
        $deletedEUBranches = EUBranch::where('registration', $company->reg_com)->delete();

        $this->line('Deleted related records:');
        $this->line("  - addresses: {$deletedAddresses}");
        $this->line("  - infos: {$deletedInfo}");
        $this->line("  - statuses: {$deletedStatuses}");
        $this->line("  - caen_companies: {$deletedCaen}");
        $this->line("  - legal_representatives: {$deletedLegalReps}");
        $this->line("  - natural_person_representatives: {$deletedNaturalReps}");
        $this->line("  - e_u_branches: {$deletedEUBranches}");
    }
}
