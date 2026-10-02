<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Dtos\SearchCriteriaField;

/**
 * @extends Collection<string, SearchCriteriaField>
 */
final class SearchCriteriaFieldCollection extends Collection
{
    /**
     * @param  iterable<array-key, SearchCriteriaField>  $fields
     */
    public static function fromFields(iterable $fields): self
    {
        $collection = new self;

        foreach ($fields as $field) {
            $collection->put($field->key, $field);
        }

        return $collection;
    }

    /**
     * The fields of each category, keyed by the category name.
     *
     * @return Collection<string, self>
     */
    public function byCategory(): Collection
    {
        $categories = [];

        foreach ($this as $field) {
            $categories[$field->category] ??= new self;
            $categories[$field->category]->put($field->key, $field);
        }

        return new Collection($categories);
    }
}
