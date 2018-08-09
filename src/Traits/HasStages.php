<?php

namespace NastuzziSamy\Laravel\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * This trait add multiple scopes into model class.
 * They are all usable directly by calling them (withtout the "scope" behind) when querying for items.
 * It is usefull for model with parent and children relation in itselft
 *
 * It is also possible to customize this property:
 *  - `parent_id` to define the parental column
 */
Trait HasStages {
    /**
     * Get root models (without parents)
     * @param  Builder $query
     * @return Builder
     */
    public static function scopeTopStage(Builder $query) {
        return $query->whereNull($this->parent_id ?? 'parent_id');
    }

    public static function scopeGetTopStage(Builder $query) {
        return $this->scopeTopStage($query)->get();
    }

    /**
     * Get all models under `stage` stages of root models
     * @param  Builder $query
     * @return Builder
     */
    public function scopeStage(Builder $query, int $stage) {
        $lastAlias = $this->getTable();

		for ($i = 0; $i < $stage; $i++) {
            $alias = $this->getTable().'-'.$i;

            $query = $query->join(
                $this->getTable().' as '.$alias,
                $alias.'.'.($this->parent_id ?? 'parent_id'),
                '=',
                $lastAlias.'.id'
            );

            $lastAlias = $alias;
		}

        $query = $query->whereNull($this->getTable().'.'.($this->parent_id ?? 'parent_id'));

        $query->getQuery()->columns = [$lastAlias.'.*'];

        return $query;
	}

    public static function scopeGetStage(Builder $query, int $stage) {
        return $this->scopeStage($query, $stage)->get();
    }

    private function addChildrenToTree(Collection $collection, Model $model, array $ids) {
        // We make sure that the `children` property exists
        $model->children = new Collection;

        if (in_array($model->{$this->parent_id ?? 'parent_id'}, $ids)) {
            foreach ($collection as $modelOfCollection) {
                if ($modelOfCollection->id === $model->{$this->parent_id ?? 'parent_id'}) {
                    $modelOfCollection->children->push($model);

                    break;
                }
                else if ($modelOfCollection->children)
                    $modelOfCollection->children = $this->addChildrenToTree($modelOfCollection->children, $model, $ids);
            }
        }
        else
            $collection->push($model);

        return $collection;
    }

    /**
     * Get all models between the `from` and `to` stages which are under `from` stages of root models
     * @param  Builder $query
     * @return Builder
     */
	public function scopeStages(Builder $query, int $from, int $to, ...$options) {
        $query = $this->scopeStage($query, $from ?? 0);
        $lastAlias = $this->getTable().(($from ?? 0) === 0 ? '' : '-'.($from - 1));

        if (count($options) === 0)
            $option = 'flat';
        else {
            $option = $options[0];
            unset($options[0]);

            if (count($options) > 0)
                $query = $query->whereIn($lastAlias.'.id', $options);
        }

		for ($i = $from ?? 0; $i < $to; $i++) {
            $alias = $this->getTable().'-'.$i;

            $query = $query->join($this->getTable().' as '.$alias, function ($join) use ($alias, $lastAlias) {
                $join->on(
                    $alias.'.'.($this->parent_id ?? 'parent_id'),
                    '=',
                    $lastAlias.'.id'
                );
                $join->orOn(
                    $alias.'.id',
                    '=',
                    $lastAlias.'.id'
                );
            });

            $lastAlias = $alias;
		}

        if (($from ?? 0) === 0)
            $query = $query->whereNull($this->getTable().'.'.($this->parent_id ?? 'parent_id'));

        $query = $query->distinct();
        $query->getQuery()->columns = [$lastAlias.'.*'];

        switch ($option) {
            case 'tree':
                $collection = new Collection;
                $models = $query->get();
                $passed_ids = [];

                foreach ($models as $model) {
                    $collection = $this->addChildrenToTree($collection, $model, $passed_ids);

                    $passed_ids[] = $model->id;
                }

                return $collection;
                break;

            case 'flat':
            default:
                return $query;
                break;
        }
	}

    public function scopeGetStages(Builder $query, int $from, int $to, string $option = 'flat') {
        $query = $this->scopeStages($query, $from, $to, $option);

        return $query instanceof Builder ? $query->get() : $query;
    }
}
