<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\BannedCifService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;

/**
 * The company page is cached as plain arrays (attributes plus relations), so a
 * cache hit rebuilds the models without issuing a single query and a model
 * change between deploys cannot break an unserialized Eloquent model.
 *
 * @phpstan-type SerializedModel array{attributes: array<string, mixed>, relations: array<string, mixed>}
 */
class CompanyController extends Controller
{
    /**
     * Relations rendered on the company page.
     *
     * @var array<int, string>
     */
    private const COMPANY_RELATIONS = [
        'address',
        'info',
        'status.details',
        'caen.details',
        'legalRepresentatives',
        'naturalPersonRepresentatives',
        'euBranches',
    ];

    /**
     * A CIF can belong to more than one company (the old, deregistered one and
     * a newly registered one), so the still operating company wins.
     */
    public function show(string $cui)
    {
        // Validate CUI format (numeric only)
        if (! ctype_digit($cui)) {
            abort(404);
        }

        // Banned CIFs are skipped at import time; a row that was imported
        // before the ban is enforced must not be published either.
        if (BannedCifService::isBanned($cui)) {
            abort(404);
        }

        // `v2`: entries written before the array format hold serialized
        // models, which this code cannot read; a new key lets them expire.
        /** @var SerializedModel $cached */
        $cached = Cache::remember(
            "company_page_v2_{$cui}",
            now()->addDay(),
            function () use ($cui): array {
                $company = Company::query()
                    ->with(self::COMPANY_RELATIONS)
                    ->where('cui', $cui)
                    ->preferActive()
                    ->orderByDesc('registration_date')
                    ->firstOrFail();

                return [
                    'attributes' => $company->getAttributes(),
                    'relations' => $this->serializeRelations($company),
                ];
            }
        );

        $company = (new Company)->newFromBuilder($cached['attributes']);
        $this->restoreRelations($company, $cached['relations']);

        return view('companies.show', compact('company'));
    }

    /**
     * Flatten the loaded relations (and their nested relations) into arrays.
     *
     * @return array<string, mixed>
     */
    private function serializeRelations(Model $model): array
    {
        $serialized = [];

        foreach ($model->getRelations() as $name => $value) {
            $serialized[$name] = match (true) {
                $value instanceof Model => $this->serializeModel($value),
                $value instanceof Collection => $value
                    ->map(fn (Model $related): array => $this->serializeModel($related))
                    ->all(),
                default => null,
            };
        }

        return $serialized;
    }

    /**
     * @return SerializedModel
     */
    private function serializeModel(Model $model): array
    {
        return [
            'attributes' => $model->getAttributes(),
            'relations' => $this->serializeRelations($model),
        ];
    }

    /**
     * Rebuild the relations on a model built with `newFromBuilder`.
     *
     * @param  array<string, mixed>  $relations
     */
    private function restoreRelations(Model $model, array $relations): void
    {
        foreach ($relations as $name => $serialized) {
            /** @var Relation<Model, Model, mixed> $relation */
            $relation = $model->{$name}();
            $related = $relation->getRelated();

            if (! is_array($serialized)) {
                $model->setRelation($name, null);

                continue;
            }

            $model->setRelation(
                $name,
                array_is_list($serialized)
                    ? $related->newCollection(array_map(
                        fn (array $item): Model => $this->restoreModel($related, $item),
                        $serialized,
                    ))
                    : $this->restoreModel($related, $serialized),
            );
        }
    }

    /**
     * @param  SerializedModel  $serialized
     */
    private function restoreModel(Model $prototype, array $serialized): Model
    {
        $model = $prototype->newFromBuilder($serialized['attributes']);
        $this->restoreRelations($model, $serialized['relations']);

        return $model;
    }
}
