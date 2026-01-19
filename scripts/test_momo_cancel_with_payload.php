<?php
// Simulate MoMo redirect with cancellation result and extraData payload
$payload = [
    'booking' => [
        'type' => 'single',
        'field_id' => 5,
        'booking_date' => '2025-12-19',
        'start_time' => '06:00',
        'duration' => 1,
        'rent_ball' => 1,
        'rent_uniform' => 1,
        'note' => '',
        'total_price' => 450000
    ],
    'user_id' => '2'
];
$extra = base64_encode(json_encode($payload));
$url = "http://localhost/websanbong/momo_post.php?resultCode=0&orderId=SIM_SUCCESS_PAYLOAD&amount=175000&extraData=" . urlencode($extra);
echo "Requesting: $url\n";
$resp = file_get_contents($url);
echo "Response length: " . strlen($resp) . "\n";
echo substr($resp, 0, 500) . "\n";
?>