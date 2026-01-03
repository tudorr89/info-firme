<?php

namespace App\Livewire;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
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

    #[Url]
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
        // Get search from URL parameter (check both 'q' and 'search')
        if (request()->has('q')) {
            $this->search = request()->get('q', '');
        } elseif (request()->has('search')) {
            $this->search = request()->get('search', '');
        }

        // Initialize filters with empty arrays if not set
        if (empty($this->filters['status'])) {
            $this->filters['status'] = [];
        }
        if (empty($this->filters['caen'])) {
            $this->filters['caen'] = [];
        }
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

    public function search(): void
    {
        $this->redirect(route('company.search', ['search' => $this->search]));
    }

    public function filterByCaen(int $caenCode): void
    {
        $this->filters['caen'] = [$caenCode];
        $this->resetPage();
    }

    private function getCacheKey(): string
    {
        $filters = $this->filters;
        ksort($filters);
        $filterKey = json_encode($filters);

        return 'company_search:'.hash('sha256', $this->search.$filterKey);
    }

    private function getQuery()
    {
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
            ->orderByDesc('registration_date');
    }

    public function getResultsProperty()
    {
        // Only load results if user has searched or applied filters
        if (! $this->search && count(array_filter($this->filters)) === 0) {
            return new \Illuminate\Pagination\Paginator([], 20, 1);
        }

        $cacheKey = $this->getCacheKey();
        $cacheMinutes = 15;

        // Try to get total count from cache
        $totalCount = Cache::get($cacheKey.':count');

        if ($totalCount === null) {
            $totalCount = $this->getQuery()->count();
            Cache::put($cacheKey.':count', $totalCount, now()->addMinutes($cacheMinutes));
        }

        // Get paginated results (pagination is not cached since it can vary per request)
        $results = $this->getQuery()->paginate(20);

        return $results;
    }

    public function render()
    {
        return view('livewire.company-search', [
            'results' => $this->results,
        ]);
    }
}
