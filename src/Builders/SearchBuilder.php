<?php

namespace Mysticquent\Builders;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mysticquent\Builders\BaseBuilder;
use Mysticquent\Builders\Utilities\FilterEndpoint;
use Mysticquent\Collection\MysticquentPaginator;
use Mysticquent\Exceptions\InvalidArgumentException;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\CommonTermsQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\FullText\SimpleQueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoPolygonQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceRangeQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\FuzzyQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RegexpQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search as Query;
use ONGR\ElasticsearchDSL\SearchEndpoint\QueryEndpoint;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class SearchBuilder extends BaseBuilder
{
    /**
    * Keyword for query.
    *
    * @var string
    */
    protected $keyword = '*';

    /**
    * Offset for query.
    *
    * @var string
    */
    protected $offset = 0;

    /**
     * Limit item for query.
     *
     * @var string
     */
    protected $limit = 30;

    /**
     * Query bool state.
     *
     * @var string
     */
    protected $boolState = BoolQuery::MUST;

    /**
     * Eager load.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Builder constructor.
     *
     * @param array attributes
     */
    public function __construct(string $keyword = '*', array $attributes = [])
    {
        parent::__construct();
        $this->keyword = $keyword ?? '*';
        $this->setPagination($attributes);
        $filters = Arr::get($attributes, 'where', []);
        $this->addFilters($filters);
        $queries = Arr::get($attributes, 'query', []);
        $this->addQueries($queries);
        $sortBy = Arr::get($attributes, 'sort_by', []);
        $this->addSortBy($sortBy);
        $this->setSearchFields($attributes);
        $this->setWith(Arr::flatten([Arr::get($attributes, 'with', [])]));
        $this->setIndex(Arr::flatten([Arr::get($attributes, 'index', ['_all'])]));
    }

    /**
     * Get offset for query.
     *
     * @return int
     */
    public function getOffset() : int
    {
        return $this->offset;
    }

    /**
     * Set offset for query.
     *
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset(int $offset)
    {
        if ($offset >= 0) {
            $this->offset = $offset;
        }

        return $this;
    }

    /**
     * Get limit item for query.
     *
     * @return int
     */
    public function getLimit() : int
    {
        return $this->limit;
    }

    /**
     * Set limit item for query.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit(int $limit)
    {
        if ($limit >= 0) {
            $this->limit = $limit;
        }

        return $this;
    }

    /**
     * Get page for pagination.
     *
     * @return int
     */
    public function getPage() : int
    {
        if ($this->limit == 0) {
            return $this->offset + 1;
        }
        return (int) (($this->offset/$this->limit) + 1);
    }

    /**
     * Set page for pagination.
     *
     * @param int $page
     *
     * @return $this
     */
    public function setPage(int $page)
    {
        if ($page > 0) {
            $this->offset = ($page - 1) * $this->limit;
        }

        return $this;
    }

    /**
     * Get item per page for pagination.
     *
     * @return int
     */
    public function getPerPage() : int
    {
        return $this->getLimit();
    }

    /**
     * Set item per page for pagination.
     *
     * @param int $page
     *
     * @return $this
     */
    public function setPerPage(int $perPage)
    {
        if ($perPage > 0) {
            $page = $this->getPage();
            $this->setLimit($perPage);
            $this->offset = ($page - 1) * $this->limit;
        }

        return $this;
    }

    /**
     * Get with for eager load.
     *
     * @return array
     */
    public function getWith() : array
    {
        return $this->with;
    }

    /**
     * Set with for eager load.
     *
     * @param array $with
     *
     * @return $this
     */
    public function setWith(array $with)
    {
        $this->with = $with;

        return $this;
    }

    /**
     * Set the query from/offset value.
     *
     * @param int $offset
     *
     * @return $this
     */
    public function from($offset)
    {
        $this->query->setFrom($offset);

        return $this;
    }

    /**
     * Set the query limit/size value.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function size($limit)
    {
        $this->query->setSize($limit);

        return $this;
    }

    /**
     * Set the query sort values values.
     *
     * @param string|array $fields
     * @param null $order
     * @param array $parameters
     *
     * @return $this
     */
    public function sortBy($fields, $order = null, array $parameters = [])
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            $sort = new FieldSort($field, $order, $parameters);

            $this->query->addSort($sort);
        }

        return $this;
    }

    /**
     * Set the query min score value.
     *
     * @param $score
     *
     * @return $this
     */
    public function minScore($score)
    {
        $this->query->setMinScore($score);

        return $this;
    }

    /**
     * Switch to a should statement.
     */
    public function should()
    {
        $this->boolState = BoolQuery::SHOULD;

        return $this;
    }

    /**
     * Switch to a must statement.
     */
    public function must()
    {
        $this->boolState = BoolQuery::MUST;

        return $this;
    }

    /**
     * Switch to a must not statement.
     */
    public function mustNot()
    {
        $this->boolState = BoolQuery::MUST_NOT;

        return $this;
    }

    /**
     * Switch to a filter query.
     */
    public function filter()
    {
        $this->boolState = BoolQuery::FILTER;

        return $this;
    }

    /**
     * Add an ids query.
     *
     * @param array | string $ids
     *
     * @return $this
     */
    public function ids($ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $query = new IdsQuery($ids);

        $this->append($query);

        return $this;
    }

    /**
     * Add an term query.
     *
     * @param string $field
     * @param string $term
     * @param array $attributes
     *
     * @return $this
     */
    public function term($field, $term, array $attributes = [])
    {
        $query = new TermQuery($field, $term, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add an terms query.
     *
     * @param string $field
     * @param array $terms
     * @param array $attributes
     *
     * @return $this
     */
    public function terms($field, array $terms, array $attributes = [])
    {
        $query = new TermsQuery($field, $terms, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add an exists query.
     *
     * @param string|array $fields
     *
     * @return $this
     */
    public function exists($fields)
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            $query = new ExistsQuery($field);

            $this->append($query);
        }

        return $this;
    }

    /**
     * Add a wildcard query.
     *
     * @param string $field
     * @param string $value
     * @param float $boost
     *
     * @return $this
     */
    public function wildcard($field, $value, $boost = 1.0)
    {
        $query = new WildcardQuery($field, $value, ['boost' => $boost]);

        $this->append($query);

        return $this;
    }

    /**
     * Add a boost query.
     *
     * @param float|null $boost
     *
     * @return $this
     *
     * @internal param $field
     */
    public function matchAll($boost = 1.0)
    {
        $query = new MatchAllQuery(['boost' => $boost]);

        $this->append($query);

        return $this;
    }

    /**
     * Add a match query.
     *
     * @param string $field
     * @param string $term
     * @param array $attributes
     *
     * @return $this
     */
    public function match($field, $term, array $attributes = [])
    {
        $query = new MatchQuery($field, $term, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a multi match query.
     *
     * @param array $fields
     * @param string $term
     * @param array $attributes
     *
     * @return $this
     */
    public function multiMatch(array $fields, $term, array $attributes = [])
    {
        $query = new MultiMatchQuery($fields, $term, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo bounding box query.
     *
     * @param string $field
     * @param array $values
     * @param array $parameters
     *
     * @return $this
     */
    public function geoBoundingBox($field, $values, array $parameters = [])
    {
        $query = new GeoBoundingBoxQuery($field, $values, $parameters);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo distance query.
     *
     * @param string $field
     * @param string $distance
     * @param mixed $location
     * @param array $attributes
     *
     * @return $this
     */
    public function geoDistance($field, $distance, $location, array $attributes = [])
    {
        $query = new GeoDistanceQuery($field, $distance, $location, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo distance range query.
     *
     * @param string $field
     * @param $from
     * @param $to
     * @param mixed $location
     * @param array $attributes
     *
     * @return $this
     */
    public function geoDistanceRange($field, $from, $to, array $location, array $attributes = [])
    {
        $range = compact('from', 'to');

        $query = new GeoDistanceRangeQuery($field, $range, $location, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo polygon query.
     *
     * @param string $field
     * @param array $points
     * @param array $attributes
     *
     * @return $this
     */
    public function geoPolygon($field, array $points = [], array $attributes = [])
    {
        $query = new GeoPolygonQuery($field, $points, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo shape query.
     *
     * @param string $field
     * @param $type
     * @param array $coordinates
     * @param array $attributes
     *
     * @return $this
     */
    public function geoShape($field, $type, array $coordinates = [], array $attributes = [])
    {
        $query = new GeoShapeQuery();

        $query->addShape($field, $type, $coordinates, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a prefix query.
     *
     * @param string $field
     * @param string $term
     * @param array $attributes
     *
     * @return $this
     */
    public function prefix($field, $term, array $attributes = [])
    {
        $query = new PrefixQuery($field, $term, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a query string query.
     *
     * @param string $query
     * @param array $attributes
     *
     * @return $this
     */
    public function queryString($query, array $attributes = [])
    {
        $query = new QueryStringQuery($query, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a simple query string query.
     *
     * @param string $query
     * @param array $attributes
     *
     * @return $this
     */
    public function simpleQueryString($query, array $attributes = [])
    {
        $query = new SimpleQueryStringQuery($query, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a range query.
     *
     * @param string $field
     * @param array $attributes
     *
     * @return $this
     */
    public function range($field, array $attributes = [])
    {
        $query = new RangeQuery($field, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a regexp query.
     *
     * @param string $field
     * @param array $attributes
     *
     * @return $this
     */
    public function regexp($field, $regex, array $attributes = [])
    {
        $query = new RegexpQuery($field, $regex, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a common term query.
     *
     * @param $field
     * @param $term
     * @param array $attributes
     *
     * @return $this
     */
    public function commonTerm($field, $term, array $attributes = [])
    {
        $query = new CommonTermsQuery($field, $term, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a fuzzy query.
     *
     * @param $field
     * @param $term
     * @param array $attributes
     *
     * @return $this
     */
    public function fuzzy($field, $term, array $attributes = [])
    {
        $query = new FuzzyQuery($field, $term, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a nested query.
     *
     * @param $field
     * @param \Closure $closure
     * @param string $score_mode
     *
     * @return $this
     */
    public function nested($field, \Closure $closure, $score_mode = 'avg')
    {
        $builder = new self($this->connection, new $this->query());

        $closure($builder);

        $nestedQuery = $builder->query->getQueries();

        $query = new NestedQuery($field, $nestedQuery, ['score_mode' => $score_mode]);

        $this->append($query);

        return $this;
    }

    /**
     * Add aggregation.
     *
     * @param \Closure $closure
     *
     * @return $this
     */
    public function aggregate(\Closure $closure)
    {
        $builder = new AggregationBuilder($this->query);

        $closure($builder);

        return $this;
    }

    /**
     * Execute the search query against elastic and return the raw result.
     *
     * @return array
     */
    public function getRaw()
    {
        $params = [
            'index' => $this->getIndex(),
            'body' => $this->toDSL(),
        ];

        return $this->getClient()->search($params);
    }

    /**
     * Execute the search query against elastic and return the result query.
     *
     * @return MysticquentPaginator
     */
    public function get()
    {
        $result = $this->from($this->getOffset())->size($this->getLimit())->getRaw();
        $hits = new Collection(Arr::get($result, 'hits.hits', []));
        $sortId = [];
        $bucket = [];
        for ($i=0; $i < $hits->count() ; $i++) {
            $hit = $hits->get($i);
            $bucket[$hit['_type']][] = $hit;
            $model = $this->getModelMap($hit['_type']);
            $key = $model.':'.$hit['_id'];
            $sortId[$key] = $i;
        }
        $collection = new Collection();
        foreach ($bucket as $type => $collect) {
            $collection = $collection->merge($this->load($type, new Collection($collect), $this->getWith()));
        }
        $collection = $collection->sortBy(function ($element) use ($sortId){
            $key = get_class($element).':'.$element->getKey();
            return Arr::get($sortId, $key, 10001);
        })->values();
        return new MysticquentPaginator($collection, Arr::get($result, 'hits.total', 0), $this->getLimit(), $this->getPage(), Arr::get($result, 'aggregations', []));
    }

    /**
     * Execute the search query against elastic and return the result of aggregation.
     *
     * @return array
     */
    public function getAggregations()
    {
        $result = $this->getRaw();
        return Arr::get($result, 'aggregations', []);
    }

    /**
     * Paginate result hits.
     *
     * @param int $limit
     *
     * @return MysticquentPaginator
     */
    public function paginate($limit = 30, $page = null)
    {
        if (is_null($page)) {
            $page = $this->getPage();
        }
        $this->setLimit($limit);
        $this->setPage($page);

        return $this->get();
    }

    /**
     * Execute the search query against elastic and return total.
     *
     * @return int
     */
    public function total()
    {
        $result = $this->from($this->getOffset())->size($this->getLimit())->getRaw();
        return Arr::get($result, 'hits.total', 0);
    }

    /**
     * Return the boolean query state.
     *
     * @return string
     */
    public function getBoolState()
    {
        return $this->boolState;
    }

    /**
     * Return the DSL query.
     *
     * @return array
     */
    public function toDSL()
    {
        return $this->query->toArray();
    }

    /**
     * Append a query.
     *
     * @param $query
     *
     * @return $this
     */
    public function append($query)
    {
        $this->query->addQuery($query, $this->getBoolState());

        return $this;
    }

    /**
     * Filter must type.
     *
     * @param array|string $fields
     * @param $mixed $value
     * @param array $attributes
     *
     * @return $this
     */
    public function where($fields, $value, $attributes = [])
    {
        if (is_array($fields) && array() === $fields) {
            throw new InvalidArgumentException('fields must be associative array or string');
        }
        $this->must();
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $this->where($key, $val);
            }
        } else {
            $this->addCondition(true, $fields, $value, $attributes);
        }

        return $this;
    }

    /**
     * Filter should type.
     *
     * @param array|string $fields
     * @param $mixed $value
     * @param array $attributes
     *
     * @return $this
     */
    public function whereOr($fields, $value, $attributes = [])
    {
        if (is_array($fields) && array() === $fields) {
            throw new InvalidArgumentException('fields must be associative array or string');
        }
        $this->should();
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $this->whereOr($key, $val);
            }
        } else {
            $this->addCondition(true, $fields, $value, $attributes);
        }

        return $this;
    }

    /**
     * Filter must not type.
     *
     * @param array|string $fields
     * @param $mixed $value
     * @param array $attributes
     *
     * @return $this
     */
    public function whereNot($fields, $value, $attributes = [])
    {
        if (is_array($fields) && array() === $fields) {
            throw new InvalidArgumentException('fields must be associative array or string');
        }
        $this->mustNot();
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $this->whereNot($key, $val);
            }
        } else {
            $this->addCondition(true, $fields, $value, $attributes);
        }

        return $this;
    }

    /**
     * Query must type.
     *
     * @param array|string $fields
     * @param $mixed $value
     * @param array $attributes
     *
     * @return $this
     */
    public function query($fields, $value, $attributes = [])
    {
        if (is_array($fields) && array() === $fields) {
            throw new InvalidArgumentException('fields must be associative array or string');
        }
        $this->must();
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $this->query($key, $val);
            }
        } else {
            $this->addCondition(false, $fields, $value, $attributes);
        }

        return $this;
    }

    /**
     * Query should type.
     *
     * @param array|string $fields
     * @param $mixed $value
     * @param array $attributes
     *
     * @return $this
     */
    public function queryOr($fields, $value, $attributes = [])
    {
        if (is_array($fields) && array() === $fields) {
            throw new InvalidArgumentException('fields must be associative array or string');
        }
        $this->should();
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $this->queryOr($key, $val);
            }
        } else {
            $this->addCondition(false, $fields, $value, $attributes);
        }

        return $this;
    }

    /**
     * Query must not type.
     *
     * @param array|string $fields
     * @param $mixed $value
     * @param array $attributes
     *
     * @return $this
     */
    public function queryNot($fields, $value, $attributes = [])
    {
        if (is_array($fields) && array() === $fields) {
            throw new InvalidArgumentException('fields must be associative array or string');
        }
        $this->mustNot();
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $this->queryNot($key, $val);
            }
        } else {
            $this->addCondition(false, $fields, $value, $attributes);
        }

        return $this;
    }

    /**
     * Set pagination.
     *
     * @param array $attributes
     *
     */
    private function setPagination(array $attributes)
    {
        $currentPage = Arr::get($attributes, 'page', \Request::input('page', 1));
        $currentPerPage = Arr::get($attributes, 'per_page', \Request::input('per_page', 30));
        $currentOffset = Arr::get($attributes, 'offset', \Request::input('offset', ($currentPerPage * ($currentPage - 1))));
        $currentLimit = Arr::get($attributes, 'limit', \Request::input('limit', $currentPerPage));
        $this->setOffset($currentOffset);
        $this->setLimit($currentLimit);
    }

    /**
     * Add condition.
     *
     * @param bool $filter
     * @param string $field
     * @param mixed $value
     * @param array $attributes
     *
     */
    private function addCondition(bool $filter, string $field, $value, $attributes = []) : void
    {
        $query = null;
        if (is_null($value)) {
            if ($this->getBoolState() == BoolQuery::MUST_NOT) {
                $this->must();
            }
            $this->exists($field);
        } elseif (is_array($value) && !is_associative_array($value)) {
            $query = new TermsQuery($field, $value, $attributes);
        } elseif (is_associative_array($value)) {
            if (Arr::has($value, 'gt') || Arr::has($value, 'gte') ||
                Arr::has($value, 'lt') || Arr::has($value, 'lte')) {
                $query = new RangeQuery($field, $value);
            } elseif (Arr::has($value, 'not')) {
                if ($filter) {
                    $this->whereNot($field, $value['not'], $attributes);
                } else {
                    $this->queryNot($field, $value['not'], $attributes);
                }
            } else {
                $query = new TermsQuery($field, $value, $attributes);
            }
        } else {
            $query = new TermQuery($field, $value, $attributes);
        }
        if ($filter) {
            $this->filter();
        }
        if ($query) {
            $this->append($query);
        }
    }

    /**
     * Add filters.
     *
     * @param array $attributes
     *
     */
    private function addFilters(array $filters) : void
    {
        if (is_array($filters) && array() === $filters && !empty($filters)) {
            throw new InvalidArgumentException('attribute where must be associative array');
        }
        foreach ($filters as $clauses => $value) {
            switch ($clauses) {
                case 'or':
                    foreach ($value as $key => $val) {
                        $this->whereOr($key, $val);
                    }
                    break;
                case 'not':
                    foreach ($value as $key => $val) {
                        $this->whereNot($key, $val);
                    }
                    break;
                default:
                    $this->where($clauses, $value);
                    break;
            }
        }
    }

    /**
     * Add queries.
     *
     * @param array $attributes
     *
     */
    private function addQueries(array $queries) : void
    {
        if (is_array($queries) && array() === $queries && !empty($queries)) {
            throw new InvalidArgumentException('attribute query must be associative array');
        }
        foreach ($queries as $clauses => $value) {
            switch ($clauses) {
                case 'or':
                    foreach ($value as $key => $val) {
                        $this->queryOr($key, $val);
                    }
                    break;
                case 'not':
                    foreach ($value as $key => $val) {
                        $this->queryNot($key, $val);
                    }
                    break;
                default:
                    $this->query($clauses, $value);
                    break;
            }
        }
    }

    /**
     * Add fields for sorting.
     *
     * @param mixed $attributes
     *
     */
    private function addSortBy($fields) : void
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        if (is_array($fields)) {
            foreach ($fields as $key => $order) {
                if ($key == 'id' || $order == 'id') {
                    continue;
                }
                if (is_numeric($key)) {
                    $this->sortBy($order, 'asc');
                } else {
                    $this->sortBy($key, $order);
                }
            }
        }
    }

    /**
     * Set fields for search.
     *
     * @param array $attributes
     *
     */
    private function setSearchFields(array $attributes) : void
    {
        $fields = Arr::get($attributes,
                            'fields',
                            []);

        $fields = Arr::where($fields, function ($value) {
            return !preg_match('/(_id)$/', $value);
        });
        $fields = Arr::flatten($fields);
        if (!empty($this->keyword) && $this->keyword != '*') {
            if (empty($fields)) {
                $this->match('_all', $this->keyword, ['fuzziness' => 'AUTO']);
            } else {
                $this->multiMatch($fields, $this->keyword, ['fuzziness' => 'AUTO']);
            }
        }
    }

    /**
     * Load model.
     *
     * @param string $type
     * @param Collection $hits
     * @param array $attributes
     *
     * @return Collection
     */
    private function load(string $type, Collection $hits, array $with = []) : Collection
    {
        $model = $this->getModelMap($type);
        $primaryKeys = $hits->map(function ($item){
            return $item['_id'];
        })->unique()->values()->all();

        $models = $model::with($with)->find($primaryKeys);
        return $hits->map(function ($item) use ($models, $type) {
            $exists = $models->search(function ($model) use ($item, $type) {
                return $model->getKey() == $item['_id'];
            });
            return $models->get($exists) ?? $this->fillModel($type, $item);
        });
    }

    /**
     * Fill model for Dummy Model.
     *
     * @param string $type
     * @param array $attributes
     *
     * @return mixed
     */
    private function fillModel(string $type, array $attributes)
    {
        $model = $this->getModelMap($type);
        $instance = new $model();

        $instance->unguard();
        $instance->fill($attributes);
        $instance->reguard();

        return $instance;
    }

    /**
     * Get Model Mapping.
     *
     * @param string $type
     *
     * @return string
     */
    private function getModelMap(string $type) : string
    {
        $map = config('mysticquent.mappings', []);
        $model = Arr::get($map, $type, $type);
        return $model;
    }
}
