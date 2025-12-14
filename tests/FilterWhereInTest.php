<?php

namespace Tijanidevit\QueryFilter\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class FilterWhereInTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->integer('stock')->nullable();
            $table->boolean('visible')->nullable();
        });

        TestProduct::create(['category' => 'electronics', 'brand' => 'lg', 'stock' => 10, 'visible' => true]);
        TestProduct::create(['category' => 'electronics', 'brand' => 'samsung', 'stock' => 0, 'visible' => false]);
        TestProduct::create(['category' => 'fashion', 'brand' => 'nike', 'stock' => 25, 'visible' => true]);
        TestProduct::create(['category' => 'fashion', 'brand' => 'adidas', 'stock' => 3, 'visible' => false]);
    }

    /** @test */
    public function it_filters_single_column()
    {
        $results = TestProduct::query()
            ->filterWhereIn('category', ['fashion'])
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_filters_using_associative_array()
    {
        $results = TestProduct::query()
            ->filterWhereIn([
                'brand' => ['lg', 'nike']
            ])->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_returns_all_if_value_is_null()
    {
        $results = TestProduct::query()
            ->filterWhereIn('category', null)
            ->get();

        $this->assertCount(4, $results);
    }

    /** @test */
    public function it_returns_all_if_value_is_empty_array()
    {
        $results = TestProduct::query()
            ->filterWhereIn('category', [])
            ->get();

        $this->assertCount(4, $results);
    }

    /** @test */
    public function it_handles_integers()
    {
        $results = TestProduct::query()
            ->filterWhereIn('stock', [10, 3])
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_handles_booleans()
    {
        $results = TestProduct::query()
            ->filterWhereIn('visible', [true])
            ->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->first()->visible);
    }

    /** @test */
    public function it_wraps_single_value_into_array()
    {
        $results = TestProduct::query()
            ->filterWhereIn('brand', 'nike')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('nike', $results->first()->brand);
    }

    /** @test */
    public function it_returns_none_if_no_match()
    {
        $results = TestProduct::query()
            ->filterWhereIn('brand', ['ghost'])
            ->get();

        $this->assertCount(0, $results);
    }
}

class TestProduct extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}
