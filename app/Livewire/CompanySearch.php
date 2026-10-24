<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Status;
use App\Services\BannedCifService;
use App\Services\CompanyStatusService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CompanySearch extends Component
{
    /**
     * InnoDB ignores full-text tokens shorter than `innodb_ft_min_token_size`
     * (3), so shorter name searches would fall back to a `LIKE '%term%'` scan
     * over more than four million rows.
     */
    private const MIN_NAME_LENGTH = 3;

    private const MIN_QUERY_LENGTH = 2;

    /**
     * Legal-form suffixes present in millions of company names. Searching for
     * "srl" alone matches about 1.8 million rows (roughly five seconds) and
     * cannot produce a meaningful top ten, so these tokens are dropped.
     *
     * @var array<int, string>
     */
    private const LEGAL_FORMS = [
        'srl', 'srld', 'scs', 'sca', 'snc', 'pfa', 'sc', 'sa', 'ii', 'if', 'ra',
    ];

    private const CANDIDATE_LIMIT = 25;

    private const VISIBLE_LIMIT = 10;

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
        $search = trim($this->search);

        if (mb_strlen($search) < self::MIN_QUERY_LENGTH) {
            return new Collection;
        }

        $ids = $this->candidateIds($search);

        if ($ids === []) {
            return new Collection;
        }

        // The same CIF can be registered more than once (an old company and a
        // newly registered one), so only the best match per CIF is listed.
        return $this->rank($ids)
            ->unique('cui')
            ->take(self::VISIBLE_LIMIT)
            ->values();
    }

    /**
     * Load the candidates with their status and order them the way the company
     * page resolves a CIF: the company that is still operating first, then the
     * newest one.
     *
     * The status is resolved in PHP instead of through the `preferActive` and
     * `presentable` scopes. Those scopes add correlated EXISTS subqueries that
     * are evaluated for the whole table, which is what made the search slow.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Company>
     */
    private function rank(array $ids): Collection
    {
        $companies = Company::query()->whereIn('id', $ids)->get();

        if ($companies->isEmpty()) {
            return $companies;
        }

        $codes = Status::query()
            ->whereIn('registration', $companies->pluck('reg_com')->all())
            ->pluck('status', 'registration');

        $activeCodes = CompanyStatusService::activeCodes();
        $obsoleteCodes = CompanyStatusService::obsoleteCodes();

        $statusOf = fn (Company $company): ?int => isset($codes[$company->reg_com])
            ? (int) $codes[$company->reg_com]
            : null;

        if (CompanyStatusService::hidesObsolete()) {
            $companies = $companies->reject(
                fn (Company $company): bool => in_array($statusOf($company), $obsoleteCodes, true)
            );
        }

        return $companies->sortBy([
            fn (Company $first, Company $second): int => (int) in_array($statusOf($second), $activeCodes, true)
                <=> (int) in_array($statusOf($first), $activeCodes, true),
            fn (Company $first, Company $second): int => $this->registeredAt($second) <=> $this->registeredAt($first),
        ])->values();
    }

    private function registeredAt(Company $company): int
    {
        return optional($company->registration_date)->timestamp ?? 0;
    }

    /**
     * Collect candidate ids from the two index-friendly matchers.
     *
     * They cannot be combined with OR in a single query: MySQL then ignores the
     * FULLTEXT index and scans all ~4.2 million rows, which took about 35
     * seconds per keystroke. An unanchored `LIKE '%term%'` has the same problem.
     *
     * @return array<int, int>
     */
    private function candidateIds(string $search): array
    {
        $cuiIds = BannedCifService::exclude(Company::query())
            ->where('cui', 'like', $search.'%')
            ->limit(self::CANDIDATE_LIMIT)
            ->pluck('id')
            ->all();

        // Digits mean a CUI look-up: the indexed prefix match already answers
        // it, and running the name search as well costs about 0.7s for a
        // numeric token that almost never appears in a name.
        if (ctype_digit($search)) {
            return $cuiIds;
        }

        if (mb_strlen($search) < self::MIN_NAME_LENGTH) {
            return $cuiIds;
        }

        $term = $this->booleanSearchTerm($search);

        if ($term === '') {
            return $cuiIds;
        }

        $nameIds = BannedCifService::exclude(Company::query())
            ->whereRaw('MATCH(name) AGAINST (? IN BOOLEAN MODE)', [$term])
            ->orderByRaw('MATCH(name) AGAINST (? IN BOOLEAN MODE) DESC', [$term])
            ->limit(self::CANDIDATE_LIMIT * 2)
            ->pluck('id')
            ->all();

        return array_values(array_unique([...$cuiIds, ...$nameIds]));
    }

    /**
     * Turn the raw input into a boolean-mode full-text term: every word is
     * required and used as a prefix, so "rti global" matches
     * "RTI GLOBAL SERVICES S.R.L.".
     */
    private function booleanSearchTerm(string $search): string
    {
        $words = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $terms = [];

        foreach ($words as $word) {
            $cleaned = preg_replace('/[+\-><()~*"@]+/u', '', $word) ?? '';

            if ($cleaned === '' || $this->isLegalForm($cleaned)) {
                continue;
            }

            $terms[] = '+'.$cleaned.'*';
        }

        return implode(' ', $terms);
    }

    private function isLegalForm(string $word): bool
    {
        $normalised = str_replace(['.', '-', ' '], '', mb_strtolower($word));

        return in_array($normalised, self::LEGAL_FORMS, true);
    }

    /**
     * True when the query holds nothing but legal-form suffixes, so it matches
     * too many companies to be useful.
     */
    public function isTooGeneric(): bool
    {
        $search = trim($this->search);

        if (mb_strlen($search) < self::MIN_NAME_LENGTH) {
            return false;
        }

        return $this->booleanSearchTerm($search) === '';
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
            'tooGeneric' => $this->isTooGeneric(),
        ])->layoutData(['seoMeta' => $seoMeta]);
    }
}
