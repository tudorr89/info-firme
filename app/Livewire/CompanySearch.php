<?php

namespace App\Livewire;

use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CompanySearch extends Component
{
    public string $search = '';

    public ?int $selectedCompanyId = null;

    public ?Company $selectedCompany = null;

    public function updatedSearch(): void
    {
        $this->selectedCompanyId = null;
        $this->selectedCompany = null;
    }

    public function selectCompany(int $companyId): void
    {
        $this->selectedCompanyId = $companyId;
        $this->selectedCompany = Company::with([
            'address',
            'info',
            'status.details',
            'caen.details',
            'legalRepresentatives',
            'naturalPersonRepresentatives',
            'euBranches',
        ])->find($companyId);
    }

    public function goBack(): void
    {
        $this->selectedCompanyId = null;
        $this->selectedCompany = null;
        $this->search = '';
    }

    #[Computed]
    public function topResults()
    {
        if (strlen($this->search) < 1) {
            return [];
        }

        return Company::query()
            ->where(function ($q) {
                $q->where('cui', $this->search)
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            })
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.company-search', [
            'topResults' => $this->topResults,
        ]);
    }
}
