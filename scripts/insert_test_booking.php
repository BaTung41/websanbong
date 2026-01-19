<?php
require __DIR__ . '/../database/DBController.php';

$user_id = 1;
$field_id = 1;
$booking_date = date('Y-m-d');
$start_time = '06:00';
$duration = 1;
$end_time = date('H:i', strtotime($booking_date . ' ' . $start_time) + ($duration * 3600));
$field_price = 100000;
$total_price = $field_price;
$note = 'Test booking for MoMo callback';
$deposit_amount = intval($total_price * 0.5);
$momo_order_id = 'SIM' . time();

$sql = "INSERT INTO bookings (user_id, field_id, booking_date, start_time, end_time, duration, field_price, rent_ball, rent_uniform, total_price, note, status, payment_method, deposit_amount, payment_status, momo_order_id) VALUES ('" . intval($user_id) . "', '" . intval($field_id) . "', '" . $booking_date . "', '" . $start_time . "', '" . $end_time . "', '" . $duration . "', '" . $field_price . "', 0, 0, '" . $total_price . "', '" . addslashes($note) . "', 'Chờ xác nhận', 'momo', '" . $deposit_amount . "', 'Đang thanh toán', '" . $momo_order_id . "')";

if (mysqli_query($conn, $sql)) {
    echo "Inserted booking with momo_order_id={$momo_order_id} and id=" . mysqli_insert_id($conn) . "\n";
} else {
    echo "Insert failed: " . mysqli_error($conn) . "\n";
}
