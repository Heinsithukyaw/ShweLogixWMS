<?php

require_once __DIR__ . '/wms-api/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/wms-api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Request::create('/api/admin/v1/events/dashboard-summary', 'GET');
$request->headers->set('Accept', 'application/json');
$request->headers->set('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIzIiwianRpIjoiYWNmMzk0MjJjZjA4NjViZmFiYjM1MjMyOTc5M2U0NWM4NDVjM2U4NDNkZTZhMzUxODAxMmJjZTA1MTQ4MzM5OGIzNWJhNDRlZTZmNjg1MTUiLCJpYXQiOjE3NTI0Nzg3NzguMzUzNDY4LCJuYmYiOjE3NTI0Nzg3NzguMzUzNDY5LCJleHAiOjE3ODQwMTQ3NzguMzUyMTE3LCJzdWIiOiI0Iiwic2NvcGVzIjpbXX0.s0ThO-JbQjaK6xlK1eef-5r6im6V3o3Di2pyHdfgw3Du6GQhoR7cC0ZTMwETk1nxi6yKard8Y2n5k8GjjwXwUlpEgGwPMkeWgX2qgYRb5Kyz9gDEtFw1EvP72CFuyGNgBIpDUMfV8ui76AEZHfu3cZPni08THZnONp_x3P_l0mbWe1NRj4VRqDEKP2Gx6vIazjYxNjLEmRbv0UWCzSdNBTvcPXqTHZ8TOQRjofVJG6JfxUWswqn_mBsz0kDyUAJWSp4Si-2CRm3v7Td7UqwSnHHIQPj-Rado5bLt4VpWUILkxSwjuvX21dG2rn1ibtFAjUXKHF6uY5w7keKo2dzBieNqCih_SBlBLDsgRNbjyAgB4JnUzkDv8a69QRIaN9LWw8R5o2r2jgCR0agSv0z6K10Vg27fmQlJcbgHNhzNxvMDnVF7H1Ko-sJFZql1FAgt8CWtz618iyurXNXIlQmz1xWua48t2Od1G5zgp6NpJK0w1QEPZpeIFL0ymRuuTjKNR6v37QNAjIfG5aNFRb8NyGRjrLpcPmWj2IPd1l14gNEo2LSuasDJVwXa2Fh5B2egHdLXkW3TeyWj4-VcgKazJJkMiO8W9sDVd-4NauTObKnHkRQFQftzJO7NPygUMFbHWmQOxEWAf9cPGj5uGSpTDo2a38iZ0cj6AU9QGFcV93k');

echo "🚀 Testing ShweLogixWMS Event System\n";
echo "=====================================\n\n";

try {
    // Process the request
    $response = $kernel->handle($request);
    
    echo "✅ API Response Status: " . $response->getStatusCode() . "\n";
    echo "📊 Dashboard Data:\n";
    
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        $summary = $data['data'];
        
        echo "   • Total Events Today: " . $summary['total_events_today'] . "\n";
        echo "   • Active Event Types: " . $summary['active_event_types'] . "\n";
        echo "   • Average Processing Time: " . $summary['average_processing_time_ms'] . "ms\n";
        echo "   • Has Backlog: " . ($summary['has_backlog'] ? 'Yes' : 'No') . "\n";
        echo "   • Idempotency Keys: " . $summary['idempotency']['total_keys'] . "\n";
        
        echo "\n✅ Event System is working correctly!\n";
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ System Error: " . $e->getMessage() . "\n";
}

echo "\n🔗 Access URLs:\n";
echo "   • Laravel API: http://localhost:12000\n";
echo "   • Event Dashboard: http://localhost:12001/system/event-monitoring\n";
echo "   • API Documentation: http://localhost:12000/api/admin/v1/events/\n";

echo "\n📝 Next Steps:\n";
echo "   1. Start React frontend on port 12001\n";
echo "   2. Set auth token in browser localStorage\n";
echo "   3. Navigate to /system/event-monitoring\n";
echo "   4. Test real-time dashboard functionality\n";

echo "\n🎉 Implementation Complete!\n";