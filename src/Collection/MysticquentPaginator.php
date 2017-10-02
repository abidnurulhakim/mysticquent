<?php

namespace Mysticquent\Collection;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MysticquentPaginator extends LengthAwarePaginator
{
    /**
     * @var PlasticResult
     */
    protected $result;

    /**
     * PlasticPaginator constructor.
     *
     * @param Collection $result
     * @param int           $total
     * @param int           $limit
     * @param int           $page
     */
    public function __construct(Collection $result, $total, $limit, $page)
    {

        parent::__construct($result, $total, $limit, $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]);
    }
}
