<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumericRouteIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public static function nonNumericIdUrls(): array
    {
        return [
            ['/api/v1/clients/abc', 'getJson'],
            ['/api/v1/events/abc', 'getJson'],
            ['/api/v1/leads/abc', 'getJson'],
            ['/api/v1/guests/abc', 'getJson'],
            ['/api/v1/vendors/abc', 'getJson'],
            ['/api/v1/venues/abc', 'getJson'],
            ['/api/v1/tickets/abc', 'getJson'],
            ['/api/v1/invoices/abc', 'getJson'],
            ['/api/v1/quotations/abc', 'getJson'],
            ['/api/v1/expenses/abc', 'getJson'],
            ['/api/v1/invoices/abc/pdf', 'getJson'],
            ['/api/v1/quotations/abc/pdf', 'getJson'],
            ['/api/v1/events/abc/budget', 'getJson'],
            ['/api/v1/events/abc/work-orders', 'getJson'],
            ['/api/v1/guests/abc/checkin', 'postJson'],
            ['/api/v1/leads/abc/convert', 'postJson'],
            ['/api/v1/events/abc/status', 'postJson'],
            ['/api/v1/tasks/abc/toggle', 'postJson'],
            ['/api/v1/work-orders/abc/payment', 'postJson'],
        ];
    }

    /**
     * @dataProvider nonNumericIdUrls
     */
    public function test_non_numeric_route_ids_return_404_not_500(string $url, string $method): void
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@eventflow.test',
            'password' => 'password',
        ])->assertOk()->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $method === 'getJson'
            ? $this->getJson($url, $headers)
            : $this->postJson($url, [], $headers);

        $this->assertSame(404, $response->getStatusCode(), "Expected 404 for {$url}, got {$response->getStatusCode()}");
    }
}
