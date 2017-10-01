<?php

use \Carbon\Carbon;

class SearchableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_gets_the_elastic_type_from_table_field_if_type_is_not_set()
    {
        $model = new SearchableModelTest();
        $this->assertEquals('SearchableModelTest', $model->getDocumentType());
    }

    /**
     * @test
     */
    public function it_gets_the_elastic_type_from_type_field_if_set()
    {
        $model = new SearchableModelTest();
        $model->documentType = 'foo';
        $this->assertEquals('foo', $model->getDocumentType());
    }

    /**
     * @test
     */
    public function it_gets_the_elastic_index_from_index_field_if_set()
    {
        $model = new SearchableModelTest();
        $model->documentIndex = 'foo';
        $this->assertEquals('foo', $model->getDocumentIndex());
    }

    /**
     * @test
     */
    public function it_returns_null_as_elastic_index_if_no_index_field()
    {
        $model = new SearchableModelTest();
        $this->assertEquals('searchable_model_tests', $model->getDocumentIndex());
    }

    /**
     * @test
     */
    public function it_gets_the_elastic_document_from_buildDocument_function_if_defined()
    {
        $model = new BuildDocumentSearchableModelTest();
        $this->assertEquals(['_suggest' => []], $model->getDocumentData());
    }

    /**
     * @test
     */
    public function it_gets_the_elastic_document_from_self_if_nothing_is_defined()
    {
        $faker = \Faker\Factory::create();
        $model = new SearchableModelTest();
        $model->id = 1;
        $model->name = 'foo';
        $model->start_at = Carbon::now();
        $model->tags = new \Illuminate\Support\Collection($faker->words());
        $this->assertEquals([
            'id' => 1,
            'name' => 'foo',
            'start_at' => $model->start_at->format('Y-m-d\TH:i:s\Z'),
            'tags' => $model->tags->toArray(),
            '_suggest' => array_merge(['foo'], $model->tags->toArray())
        ], $model->getDocumentData());

    }

    /**
     * @test
     */
    public function it_gets_sync_document_from_self_if_nothing_is_defined()
    {
        $model = new SearchableModelTest();
        $this->assertEquals(true, $model->shouldSyncDocument());
    }
}

class SearchableModelTest extends \Illuminate\Database\Eloquent\Model
{
    use \Bidzm\Mysticquent\Searchable;
}

class BuildDocumentSearchableModelTest extends \Illuminate\Database\Eloquent\Model
{
    use \Bidzm\Mysticquent\Searchable;

    public function buildDocument()
    {
        return [];
    }
}
