<?php

namespace Tijanidevit\QueryFilter\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RealLifeUsageSampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup a standard E-commerce schema
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('reference_number');
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->integer('total_amount_cents');
            $table->timestamps();
        });

        // 2. Seed realistic data
        $apple = Company::create(['name' => 'Apple Inc']);
        $google = Company::create(['name' => 'Google LLC']);

        Order::create([
            'company_id' => $apple->id,
            'reference_number' => 'ORD-12345',
            'status' => 'shipped',
            'payment_method' => 'credit_card',
            'total_amount_cents' => 150000,
            'created_at' => now()->subDays(2),
        ]);

        Order::create([
            'company_id' => $apple->id,
            'reference_number' => 'ORD-67890',
            'status' => 'pending',
            'payment_method' => 'paypal',
            'total_amount_cents' => 20000,
            'created_at' => now()->subMonth(),
        ]);

        Order::create([
            'company_id' => $google->id,
            'reference_number' => 'ORD-99999',
            'status' => 'refunded',
            'payment_method' => 'credit_card',
            'total_amount_cents' => 50000,
            'created_at' => now(),
        ]);
    }

    /**
     * @test
     * Scenario: An admin is viewing the orders dashboard. They want to filter:
     * - By specific statuses (shipped, pending)
     * - Placed in the last month
     * - Linked to the company "Apple"
     * - Sorted by newest first
     */
    public function it_handles_complex_admin_dashboard_filtering()
    {
        // Simulate a request from the dashboard frontend
        $request = new Request([
            'q_status' => 'shipped,pending',
            'q_company_name' => 'Apple',
            'date_from' => now()->subMonths(2)->format('Y-m-d'),
        ]);

        $orders = Order::query()
            // 1. Direct field mappings mapped from Request object (API: [db_column => request_key])
            // Note: Since 'q_status' requires array conversion, we use filterWhereIn immediately after for it.
            // filterFromRequest is best for simple direct matches, but here we show an example of combining them.
            
            // 2. Filter Where In with flexible comma-separated string parsing
            ->filterWhereIn('status', $request->input('q_status'))
            
            // 3. Search via relations! Finds orders where the related company matches the search term
            ->searchByRelation('company', [
                'name' => $request->input('q_company_name')
            ])
            
            // 4. Filter by Date ranges
            ->filterByDateRange(
                dateFrom: $request->input('date_from'),
                dateTo: $request->input('date_to') // Missing, gracefully ignored
            )
            
            // 5. Native syntax ordering wrapper
            ->latestBy('created_at')
            
            ->get();


        // Assertions
        $this->assertCount(2, $orders);
        $this->assertTrue($orders->pluck('reference_number')->contains('ORD-12345'));
        $this->assertTrue($orders->pluck('reference_number')->contains('ORD-67890'));
        $this->assertFalse($orders->pluck('reference_number')->contains('ORD-99999')); // Refunded Google order excluded
        
        // Ensure sorted by newest
        $this->assertEquals('ORD-12345', $orders->first()->reference_number); // 2 days ago is newer than 1 month ago
    }

    /**
     * @test
     * Scenario: An API endpoint where standard filters are uniformly collected mapped mapped directly 
     */
    public function it_demonstrates_minimalist_api_controller_usage()
    {
        // 1. The input
        $request = new Request([
            'pay_method' => 'credit_card',
            'is_shipped' => 'shipped',
        ]);

        // 2. The controller execution
        $orders = Order::query()
            ->filterFromRequest($request, [
                'payment_method' => 'pay_method', // db_column => query_param
                'status'         => 'is_shipped',
            ])
            ->get();

        // 3. The result
        $this->assertCount(1, $orders);
        $this->assertEquals('ORD-12345', $orders->first()->reference_number);
    }
}

class Company extends Model
{
    use \Tijanidevit\QueryFilter\Traits\Filterable;

    protected $guarded = [];
    public $timestamps = true;
}

class Order extends Model
{
    use \Tijanidevit\QueryFilter\Traits\Filterable;

    protected $guarded = [];
    public $timestamps = true;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
