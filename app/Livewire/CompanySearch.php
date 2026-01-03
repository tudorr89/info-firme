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

    public function selectCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $this->redirect(route('company.show', $company->cui), navigate: false);
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
        $seoMeta = view('components.seo-meta', [
            'title' => 'Căutare Companii Românești | lista-firme.info',
            'description' => 'Caută și descoperă informații despre companii românești. Peste 3.9 milioane de firme cu date oficiale: CUI, adresă, reprezentanți, cod CAEN.',
            'canonical' => route('company.search'),
        ])->render();

        return view('livewire.company-search', [
            'topResults' => $this->topResults,
        ])->layoutData(['seoMeta' => $seoMeta]);
    }
}
