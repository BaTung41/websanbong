<?php
include 'database/DBController.php';
session_start();

$field_id = $_POST['field_id'] ?? null;
$booking_date = $_POST['booking_date'] ?? null;
$duration = floatval($_POST['duration'] ?? 1);

if (!$field_id || !$booking_date) {
    echo json_encode(['bookedTimes' => []]);
    exit();
}

// Lấy tất cả các booking cho ngày này
$query = "SELECT start_time, end_time, duration 
          FROM bookings 
          WHERE field_id = '$field_id' 
          AND booking_date = '$booking_date'
          AND status IN ('Chờ xác nhận', 'Đã xác nhận')";

$result = mysqli_query($conn, $query);
$bookedTimes = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Thêm giờ bắt đầu vào danh sách đã đặt
        $start = strtotime($row['start_time']);
        $end = strtotime($row['end_time']);
        
        // Thêm tất cả các giờ từ start đến end (bao gồm cả overlapping)
        $current = $start;
        while ($current < $end) {
            $timeStr = date('H:i', $current);
            if (!in_array($timeStr, $bookedTimes)) {
                $bookedTimes[] = $timeStr;
            }
            $current += 3600; // Cộng 1 giờ
        }
    }
}

// Kiểm tra các giờ nào sẽ conflict nếu chọn
$allTimes = ['06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', 
             '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', 
             '20:00', '21:00', '22:00'];

$unavailableTimes = [];

// Block past slots for the same-day booking (based on server time)
date_default_timezone_set('Asia/Ho_Chi_Minh');
if ($booking_date == date('Y-m-d')) {
    $now = time();
    foreach ($allTimes as $time) {
        $slot_ts = strtotime("$booking_date $time");
        // If slot start time is <= now, it's not available anymore
        if ($slot_ts <= $now && !in_array($time, $unavailableTimes)) {
            $unavailableTimes[] = $time;
        }
    }
}

foreach ($allTimes as $time) {
    $startTimestamp = strtotime("$booking_date $time");
    $endTimestamp = $startTimestamp + ($duration * 3600);
    $endTime = date('H:i', $endTimestamp);
    
    // Kiểm tra conflict
    $check = "SELECT COUNT(*) as cnt FROM bookings 
              WHERE field_id = '$field_id' 
              AND booking_date = '$booking_date'
              AND status IN ('Chờ xác nhận', 'Đã xác nhận')
              AND ((start_time <= '$time' AND end_time > '$time')
              OR (start_time < '$endTime' AND end_time >= '$endTime')
              OR (start_time >= '$time' AND end_time <= '$endTime'))";
    
    $checkResult = mysqli_query($conn, $check);
    $row = mysqli_fetch_assoc($checkResult);
    
    if ($row['cnt'] > 0) {
        $unavailableTimes[] = $time;
    }
}

// Deduplicate times to avoid duplicates from multiple checks
$unavailableTimes = array_values(array_unique($unavailableTimes));

echo json_encode(['unavailableTimes' => $unavailableTimes]);
?>
