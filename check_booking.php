<?php
include 'database/DBController.php';
session_start();

$field_id = $_POST['field_id'];
$booking_date = $_POST['booking_date'];
$start_time = $_POST['start_time'];
$duration = floatval($_POST['duration']);

// Tính thời gian kết thúc
$start_timestamp = strtotime("$booking_date $start_time");
$duration_seconds = $duration * 3600;
$end_timestamp = $start_timestamp + $duration_seconds;
$end_time = date('H:i', $end_timestamp);

// Kiểm tra trùng lịch
$check_query = "SELECT b.*, f.name as field_name 
               FROM bookings b
               JOIN football_fields f ON b.field_id = f.id
               WHERE b.field_id = '$field_id' 
               AND b.booking_date = '$booking_date'
               AND b.status IN ('Chờ xác nhận', 'Đã xác nhận')
               AND ((b.start_time <= '$start_time' AND b.end_time > '$start_time')
               OR (b.start_time < '$end_time' AND b.end_time >= '$end_time')
               OR (b.start_time >= '$start_time' AND b.end_time <= '$end_time'))";

$check_booking = mysqli_query($conn, $check_query);

$response = [];
if(mysqli_num_rows($check_booking) > 0) {
    $existing_booking = mysqli_fetch_assoc($check_booking);
    $response = [
        'isBooked' => true,
        'fieldName' => $existing_booking['field_name'],
        'startTime' => $existing_booking['start_time'],
        'endTime' => $existing_booking['end_time'],
        'bookingDate' => $existing_booking['booking_date']
    ];
} else {
    $response = [
        'isBooked' => false
    ];
}

header('Content-Type: application/json');
echo json_encode($response); 