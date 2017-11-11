<?php

namespace Mysticquent\Collection;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MysticquentPaginator extends LengthAwarePaginator
{
    protected $result;

    protected $aggregation;

    /**
     * PlasticPaginator constructor.
     *
     * @param Collection $result
     * @param int           $total
     * @param int           $limit
     * @param int           $page
     */
    public function __construct(Collection $result, $total, $limit, $page, $aggregation = [])
    {

        parent::__construct($result, $total, $limit, $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]);
        $this->setAggregation($aggregation);
    }

    public function getAggregation()
    {
        return $this->aggregation;
    }

    public function setAggregation($aggregation)
    {
        $this->aggregation = $aggregation;
        return $this;
    }
}
