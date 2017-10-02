<?php

namespace Mysticquent\Builders;

use Mysticquent\Builders\BaseBuilder;
use ONGR\ElasticsearchDSL\Search as Query;
use ONGR\ElasticsearchDSL\Suggest\Suggest;

class SuggestionBuilder extends BaseBuilder
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add a completion suggestion.
     *
     * @param $name
     * @param $text
     * @param $field
     * @param array $parameters
     *
     * @return $this
     *
     * @internal param $fields
     */
    public function completion($name, $text, $field = '_suggest', $parameters = [])
    {
        $suggestion = new Suggest($name, 'completion', $text, $field, $parameters);

        $this->append($suggestion);

        return $this;
    }

    /**
     * Add a term suggestion.
     *
     * @param string $name
     * @param string $text
     * @param $field
     * @param array $parameters
     *
     * @return $this
     */
    public function term($name, $text, $field = '_all', array $parameters = [])
    {
        $suggestion = new Suggest($name, 'term', $text, $field, $parameters);

        $this->append($suggestion);

        return $this;
    }

    /**
     * Return the DSL query.
     *
     * @return array
     */
    public function toDSL()
    {
        return $this->query->toArray()['suggest'];
    }

    /**
     * Execute the suggest query against elastic and return the raw result if model not set.
     *
     * @return array
     */
    public function get()
    {
        return $this->client->suggest([
            'index' => $this->getIndex(),
            'body'  => $this->toDSL(),
        ]);
    }

    /**
     * Append a suggestion to query.
     *
     * @param $suggestion
     */
    public function append($suggestion)
    {
        $this->query->addSuggest($suggestion);
    }
}
