<?php

namespace Tijanidevit\QueryFilter\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class SearchInTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Person::create(['first_name' => 'John', 'email' => 'john@example.com', 'status' => 'active']);
        Person::create(['first_name' => 'Michael', 'email' => 'mike@example.com', 'status' => 'pending']);
        Person::create(['first_name' => 'Peter', 'email' => 'petertest@gmail.com', 'status' => 'inactive']);
    }

    /** @test */
    public function it_searches_across_multiple_string_columns()
    {
        $results = Person::query()
            ->searchIn(['first_name', 'email'], 'john')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->first_name);
    }

    /** @test */
    public function it_filters_using_where_in_on_associative_array()
    {
        $results = Person::query()
            ->searchIn(['status' => ['pending', 'active']])
            ->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('status')->contains('active'));
        $this->assertTrue($results->pluck('status')->contains('pending'));
    }

    /** @test */
    public function it_returns_all_if_value_is_empty_string()
    {
        $results = Person::query()
            ->searchIn(['first_name', 'email'], '')
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_returns_all_if_value_is_null()
    {
        $results = Person::query()
            ->searchIn(['first_name', 'email'], null)
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_returns_all_if_columns_is_null()
    {
        $results = Person::query()
            ->searchIn(null, 'test')
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_returns_empty_when_search_value_not_found()
    {
        $results = Person::query()
            ->searchIn(['first_name', 'email'], 'zzzzzzz')
            ->get();

        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_handles_numeric_value_search()
    {
        Person::create(['first_name' => 'Test123', 'email' => 'num@example.com']);

        $results = Person::query()
            ->searchIn(['first_name'], '123')
            ->get();

        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_handles_null_values_in_database_fields()
    {
        Person::create(['first_name' => null, 'email' => 'nulluser@example.com']);

        $results = Person::query()
            ->searchIn(['first_name', 'email'], 'nulluser')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('nulluser@example.com', $results->first()->email);
    }
}

class Person extends Model
{
    protected $guarded = [];
    public $timestamps = true;
}
