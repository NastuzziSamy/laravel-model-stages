<?php

namespace NastuzziSamy\Laravel\Traits;

use Illuminate\Database\Eloquent\Builder;

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

    /**
     * Get all models between the `from` and `to` stages which are under `from` stages of root models
     * @param  Builder $query
     * @return Builder
     */
	public function scopeStages(Builder $query, int $from, int $to) {
        $qery = $this->scopeStage($query, $from ?? 0);
        $lastAlias = $this->getTable().(($from ?? 0) === 0 ? '' : '-'.($from - 1));

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

        return $query->whereNull($this->getTable().'.'.($this->parent_id ?? 'parent_id'));
        if (($from ?? 0) === 0)
            $query = $query->whereNull($this->getTable().'.'.($this->parent_id ?? 'parent_id'));

        $query = $query->distinct();
        $query->getQuery()->columns = [$lastAlias.'.*'];
	}

    public function scopeGetStages(Builder $query, int $from, int $to, string $option = 'flat') {
        $query = $this->scopeStages($query, $from, $to, $option);

        return $query instanceof Builder ? $query->get() : $query;
    }
}
