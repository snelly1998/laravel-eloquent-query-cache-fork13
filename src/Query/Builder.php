<?php

namespace Rennokki\QueryCache\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Rennokki\QueryCache\Contracts\QueryCacheModuleInterface;
use Rennokki\QueryCache\Traits\QueryCacheModule;

class Builder extends BaseBuilder implements QueryCacheModuleInterface
{
    use QueryCacheModule;

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        if ($this->shouldAvoidCache()) {
            return parent::get($columns);
        }

        $results = $this->getFromQueryCache('get', Arr::wrap($columns));

        // Rows are cached as plain arrays; entries cached by older
        // versions of this package may still be Collection instances.
        return $results instanceof Collection
            ? $results
            : new Collection(array_map(fn ($row) => (object) $row, $results));
    }

    /**
     * {@inheritdoc}
     */
    public function useWritePdo()
    {
        // Do not cache when using the write pdo for query.
        $this->dontCache();

        // Call parent method
        parent::useWritePdo();

        return $this;
    }

    /**
     * Add a subselect expression to the query.
     *
     * @param  \Closure|$this|string  $query
     * @param  string  $as
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function selectSub($query, $as)
    {
        if (! is_string($query) && get_class($query) == self::class) {
            $this->appendCacheTags($query->getCacheTags() ?? []);
        }

        return parent::selectSub($query, $as);
    }
}
