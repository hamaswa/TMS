<?php

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A has-many whose foreign key is a text column pointing at a numeric id
 * (orders.customerId, transactions.orderId, ...). Ids are compared as text so
 * MySQL can use the foreign key's index instead of converting every row.
 */
class HasManyByText extends HasMany
{
    public static function of(Model $parent, string $related, string $foreignKey, string $localKey = 'id'): self
    {
        $instance = new $related;

        return new self($instance->newQuery(), $parent, $instance->qualifyColumn($foreignKey), $localKey);
    }

    public function getParentKey()
    {
        $key = parent::getParentKey();

        return $key === null ? null : (string) $key;
    }

    public function addEagerConstraints(array $models)
    {
        $this->whereInEager(
            'whereIn',
            $this->foreignKey,
            array_map('strval', $this->getKeys($models, $this->localKey)),
            $this->getRelationQuery()
        );
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $grammar = $query->getQuery()->getGrammar();

        return $query->select($columns)->whereRaw(
            $grammar->wrap($this->getExistenceCompareKey()).' = CAST('.$grammar->wrap($this->getQualifiedParentKeyName()).' AS CHAR)'
        );
    }
}
