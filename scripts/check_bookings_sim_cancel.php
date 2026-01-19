<?php
include __DIR__ . '/../database/DBController.php';
$q = mysqli_query($conn, "SELECT * FROM bookings WHERE momo_order_id IN ('SIM_CANCEL_PAYLOAD','SIM_SUCCESS_PAYLOAD') OR note LIKE '%test cancel%' LIMIT 10");
if (!$q) { echo "Query error: " . mysqli_error($conn) . "\n"; exit(1); }
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
$q2 = mysqli_query($conn, "SELECT * FROM recurring_bookings WHERE momo_order_id = 'SIM_CANCEL_PAYLOAD' LIMIT 10");
while ($r = mysqli_fetch_assoc($q2)) {
    print_r($r);
}
?>