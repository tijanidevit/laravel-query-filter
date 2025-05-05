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

        // In-memory database setup
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        User::create(['name' => 'Alice', 'status' => 'active']);
        User::create(['name' => 'Bob', 'status' => 'inactive']);
    }

    /** @test */
    public function it_filters_by_status()
    {
        $activeUsers = User::query()
            ->filterBy('status', 'active')
            ->get();

        $this->assertCount(1, $activeUsers);
        $this->assertEquals('Alice', $activeUsers->first()->name);
    }

    /** @test */
    public function it_returns_all_when_filter_value_not_passed()
    {
        $allUsers = User::query()
            ->filterBy('status', null)
            ->get();

        $this->assertCount(2, $allUsers);
    }


    /** @test */
    public function it_filters_multiple_by_array()
    {
        $activeUsers = User::query()
            ->filterBy([
                'status' => 'active',
                'name' => 'Alice',
            ])
            ->get();

        $this->assertCount(1, $activeUsers);
        $this->assertEquals('Alice', $activeUsers->first()->name);
    }

    /** @test */
    public function it_filters_all_array_by_array()
    {
        $activeUsers = User::query()
            ->filterBy([
                'status' => 'active',
                'name' => 'Bob',
            ])
            ->get();


        $this->assertCount(0, $activeUsers);
    }
}

class User extends Model
{
    protected $guarded = [];
    public $timestamps = true;
}
