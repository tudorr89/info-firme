<?php

namespace App\Livewire;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * @return Collection<int, Company>
     */
    public function topResults(): Collection
    {
        if (strlen($this->search) < 1) {
            return new Collection;
        }

        $results = Company::query()
            ->presentable()
            ->where(function ($query) {
                $query->where('cui', $this->search)
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            })
            ->preferActive()
            ->orderByDesc('registration_date')
            ->limit(25)
            ->get();

        // The same CIF can be registered more than once (an old company and a
        // newly registered one), so only the best match per CIF is listed.
        return $results
            ->unique('cui')
            ->take(10)
            ->values();
    }

    public function render()
    {
        $seoMeta = view('components.seo-meta', [
            'title' => 'Căutare Companii Românești | lista-firme.info',
            'description' => 'Caută și descoperă informații despre companii românești. Peste 3.9 milioane de firme cu date oficiale: CUI, adresă, reprezentanți, cod CAEN.',
            'canonical' => route('company.search'),
        ])->render();

        return view('livewire.company-search', [
            'topResults' => $this->topResults(),
        ])->layoutData(['seoMeta' => $seoMeta]);
    }
}
