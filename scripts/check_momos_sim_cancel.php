<?php
include __DIR__ . '/../database/DBController.php';
$q = mysqli_query($conn, "SELECT * FROM momos WHERE momo_order_id = 'SIM_CANCEL_PAYLOAD' LIMIT 10");
if (!$q) { echo "Query error: " . mysqli_error($conn) . "\n"; exit(1); }
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>