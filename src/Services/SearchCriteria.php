<?php

declare(strict_types=1);

namespace Innobrain\Structure\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Exceptions\OnOfficeException;
use Innobrain\OnOfficeAdapter\Facades\SearchCriteriaRepository;
use Innobrain\Structure\Collections\SearchCriteriaFieldCollection;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\EstateClassification;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Enums\SearchCriteriaFieldType;

use function is_array;

class SearchCriteria
{
    /**
     * Retrieve the fields a client has configured as search criteria. Field
     * and value labels are translated, category names are always German.
     *
     * @throws OnOfficeException
     */
    public function retrieveForClient(OnOfficeApiCredentials $credentials, Language $language = Language::German): SearchCriteriaFieldCollection
    {
        $categories = SearchCriteriaRepository::fields()
            ->withCredentials($credentials)
            ->parameters([
                'language' => $language->value,
                'additionalTranslations' => true,
            ])
            ->get();

        $fields = [];

        foreach ($categories as $category) {
            $categoryName = (string) Arr::get($category, 'elements.name', '');

            foreach (Arr::wrap(Arr::get($category, 'elements.fields')) as $fieldData) {
                if (! is_array($fieldData) || blank($fieldData['id'] ?? null)) {
                    continue;
                }

                $fields[] = $this->parseField($fieldData, $categoryName);
            }
        }

        return SearchCriteriaFieldCollection::fromFields($fields);
    }

    /**
     * @param  array<string, mixed>  $fieldData
     */
    private function parseField(array $fieldData, string $category): SearchCriteriaField
    {
        $key = (string) $fieldData['id'];
        $rawType = (string) Arr::get($fieldData, 'type', '');
        $rangeLabels = Arr::wrap(Arr::get($fieldData, 'additionalTranslations'));

        return new SearchCriteriaField(
            key: $key,
            label: (string) Arr::get($fieldData, 'name', $key),
            category: $category,
            rawType: $rawType,
            type: SearchCriteriaFieldType::tryFromRaw($rawType),
            isRange: $this->isTrue(Arr::get($fieldData, 'rangefield')),
            rangeFromLabel: $this->rangeLabel($rangeLabels, $key.SearchCriteriaField::RANGE_FROM_SUFFIX),
            rangeToLabel: $this->rangeLabel($rangeLabels, $key.SearchCriteriaField::RANGE_TO_SUFFIX),
            mustMatchByDefault: $this->isTrue(Arr::get($fieldData, 'ko')),
            isMandatory: $this->isTrue(Arr::get($fieldData, 'mandatory')),
            permittedValues: $this->parsePermittedValues(Arr::get($fieldData, 'values')),
            appliesTo: $this->parseAppliesTo($fieldData),
        );
    }

    /**
     * The API sends flags as the string "true" and leaves them out otherwise.
     */
    private function isTrue(mixed $flag): bool
    {
        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<array-key, mixed>  $rangeLabels
     */
    private function rangeLabel(array $rangeLabels, string $key): ?string
    {
        $label = $rangeLabels[$key] ?? null;

        return blank($label) ? null : (string) $label;
    }

    /**
     * @return Collection<string, PermittedValue>
     */
    private function parsePermittedValues(mixed $valuesData): Collection
    {
        $permittedValues = new Collection;

        if (! is_array($valuesData)) {
            return $permittedValues;
        }

        foreach ($valuesData as $key => $label) {
            $permittedValues->put((string) $key, new PermittedValue(
                key: (string) $key,
                label: (string) $label,
            ));
        }

        return $permittedValues;
    }

    /**
     * @param  array<string, mixed>  $fieldData
     * @return Collection<string, array<int, string>>
     */
    private function parseAppliesTo(array $fieldData): Collection
    {
        $appliesTo = new Collection;

        foreach (EstateClassification::cases() as $classification) {
            $values = array_values(array_filter(explode(',', (string) Arr::get($fieldData, $classification->scopeKey(), ''))));

            if ($values !== []) {
                $appliesTo->put($classification->value, $values);
            }
        }

        return $appliesTo;
    }
}
