<?php

namespace App\Livewire;

use App\Models\Company;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CompanySearch extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public array $filters = [
        'status' => [],
        'dateFrom' => null,
        'dateTo' => null,
        'county' => null,
        'caen' => [],
    ];

    public ?int $expandedCompany = null;

    public function mount(): void
    {
        // Initialize from URL if present
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function toggleExpand(int $companyId): void
    {
        $this->expandedCompany = $this->expandedCompany === $companyId ? null : $companyId;
    }

    public function clearFilters(): void
    {
        $this->filters = [
            'status' => [],
            'dateFrom' => null,
            'dateTo' => null,
            'county' => null,
            'caen' => [],
        ];
        $this->resetPage();
    }

    public function filterByCaen(int $caenCode): void
    {
        $this->filters['caen'] = [$caenCode];
        $this->resetPage();
    }

    public function getResultsProperty()
    {
        // Only load results if user has searched or applied filters
        if (! $this->search && count(array_filter($this->filters)) === 0) {
            return new \Illuminate\Pagination\Paginator([], 20, 1);
        }

        return Company::query()
            ->when(
                $this->search,
                fn ($q) => $q->where('cui', $this->search)
                    ->orWhere('name', 'like', '%'.$this->search.'%')
            )
            ->when(
                count($this->filters['status']) > 0,
                fn ($q) => $q->whereIn('status', $this->filters['status'])
            )
            ->when(
                $this->filters['dateFrom'],
                fn ($q) => $q->whereDate('registration_date', '>=', $this->filters['dateFrom'])
            )
            ->when(
                $this->filters['dateTo'],
                fn ($q) => $q->whereDate('registration_date', '<=', $this->filters['dateTo'])
            )
            ->when(
                $this->filters['county'],
                fn ($q) => $q->whereHas('address', fn ($sq) => $sq->where('county', $this->filters['county']))
            )
            ->when(
                count($this->filters['caen']) > 0,
                fn ($q) => $q->whereHas('caen', fn ($sq) => $sq->whereIn('code', $this->filters['caen']))
            )
            ->with(['address', 'info', 'status.details', 'caen', 'caen.details', 'legalRepresentatives', 'naturalPersonRepresentatives', 'euBranches'])
            ->orderByDesc('registration_date')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.company-search', [
            'results' => $this->results,
        ]);
    }
}
