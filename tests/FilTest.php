<?php

namespace Tijanidevit\QueryFilter\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class FilterByTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->integer('age')->nullable();
            $table->boolean('verified')->nullable();
        });

        TestUser::create(['role' => 'admin', 'status' => 'active', 'age' => 30, 'verified' => true]);
        TestUser::create(['role' => 'user', 'status' => 'pending', 'age' => 20, 'verified' => false]);
        TestUser::create(['role' => 'manager', 'status' => 'inactive', 'age' => 40, 'verified' => true]);
    }

    /** @test */
    public function it_filters_single_column()
    {
        $results = TestUser::query()->filterBy('role', 'admin')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('admin', $results->first()->role);
    }

    /** @test */
    public function it_filters_using_associative_array()
    {
        $results = TestUser::query()->filterBy([
            'status' => 'pending',
            'role' => 'user'
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('user', $results->first()->role);
    }

    /** @test */
    public function it_returns_all_if_value_is_null()
    {
        $results = TestUser::query()->filterBy('role', null)->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_returns_all_if_value_is_empty_string()
    {
        $results = TestUser::query()->filterBy('role', '')->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_supports_integer_filtering()
    {
        $results = TestUser::query()->filterBy('age', 30)->get();

        $this->assertCount(1, $results);
        $this->assertEquals(30, $results->first()->age);
    }

    /** @test */
    public function it_supports_boolean_filtering()
    {
        $results = TestUser::query()->filterBy('verified', true)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->first()->verified);
    }

    /** @test */
    public function it_ignores_null_values_in_array_filters()
    {
        $results = TestUser::query()->filterBy([
            'status' => null,
            'role' => 'admin',
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('admin', $results->first()->role);
    }

    /** @test */
    public function it_returns_none_if_value_matches_no_records()
    {
        $results = TestUser::query()->filterBy('role', 'ghost')->get();

        $this->assertCount(0, $results);
    }
}

class TestUser extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}
