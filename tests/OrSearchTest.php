<?php

namespace Tijanidevit\QueryFilter\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class OrSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('summary')->nullable();
            $table->timestamps();
        });

        Article::create([
            'title' => 'Learning Laravel',
            'content' => 'Eloquent is amazing for databases.',
            'summary' => 'Introduction to Laravel Eloquent'
        ]);

        Article::create([
            'title' => 'Mastering Vue',
            'content' => 'Frontend made easy.',
            'summary' => 'Vue.js guides'
        ]);

        Article::create([
            'title' => 'Advanced PHP',
            'content' => 'Building backend eloquent systems from scratch.',
            'summary' => 'Deep dive into PHP internals'
        ]);
    }

    /** @test */
    public function it_chains_orSearch_at_the_top_level_scope()
    {
        $results = Article::query()
            ->search('title', 'Laravel')
            ->orSearch(['content', 'summary'], 'eloquent')
            ->get();

        // Should return "Learning Laravel" (matches search on title)
        // AND "Advanced PHP" (matches orSearch on content containing 'eloquent')
        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('title')->contains('Learning Laravel'));
        $this->assertTrue($results->pluck('title')->contains('Advanced PHP'));
        $this->assertFalse($results->pluck('title')->contains('Mastering Vue'));
    }

    /** @test */
    public function it_works_with_associative_arrays_in_orSearch()
    {
        $results = Article::query()
            ->search('title', 'Vue')
            ->orSearch([
                'content' => 'backend',
                'summary' => 'internals'
            ])
            ->get();

        // Vue matched from title
        // Advanced PHP matched from content ('backend') and summary ('internals')
        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('title')->contains('Mastering Vue'));
        $this->assertTrue($results->pluck('title')->contains('Advanced PHP'));
    }
}

class Article extends Model
{
    use \Tijanidevit\QueryFilter\Traits\Filterable;

    protected $guarded = [];
    public $timestamps = true;
}
