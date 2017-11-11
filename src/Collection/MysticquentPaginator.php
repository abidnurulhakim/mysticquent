<?php

namespace Mysticquent\Collection;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MysticquentPaginator extends LengthAwarePaginator
{
    protected $result;

    protected $aggregations;

    /**
     * PlasticPaginator constructor.
     *
     * @param Collection $result
     * @param int           $total
     * @param int           $limit
     * @param int           $page
     */
    public function __construct(Collection $result, $total, $limit, $page, $aggregations = [])
    {

        parent::__construct($result, $total, $limit, $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]);
        $this->setAggregation($aggregation);
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }
}
