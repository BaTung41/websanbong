<?php
session_start();
require 'database/DBController.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// helper to call momo
function execPostRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        )
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($errno) {
        error_log("cURL error: $err");
        return false;
    }
    return $result;
}

$endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

// Test credentials (replace with real keys in production)
$partnerCode = 'MOMOBKUN20180529';
$accessKey = 'klm05TvNBzhg7h7j';
$secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

// Read and sanitize amount
$amount = isset($_POST['sotien']) ? preg_replace('/[^0-9]/', '', $_POST['sotien']) : 0;
if (!$amount || intval($amount) <= 0) {
    echo 'Số tiền không hợp lệ';
    exit;
}

// Determine booking type (single vs recurring)
$isRecurring = isset($_POST['start_date']) && isset($_POST['end_date']);
$orderId = time() . rand(1000, 9999);

$user_id = $_SESSION['user_id'] ?? 0;

if ($isRecurring) {
    // Gather recurring booking data (do not insert into DB yet)
    $field_id = $_POST['field_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $duration = floatval($_POST['duration'] ?? 0);
    $rent_ball = isset($_POST['rent_ball']) ? 1 : 0;
    $rent_uniform = isset($_POST['rent_uniform']) ? 1 : 0;
    $note = $_POST['note'] ?? '';
    $total_price = isset($_POST['total_price']) ? preg_replace('/[^0-9]/','', $_POST['total_price']) : 0;

    // Basic validation
    if (!$field_id || !$start_date || !$end_date || !$start_time) {
        echo 'Dữ liệu đặt sân không hợp lệ'; exit;
    }

    // Prepare booking payload to be passed in extraData
    $bookingPayload = [
        'type' => 'recurring',
        'field_id' => intval($field_id),
        'start_date' => $start_date,
        'end_date' => $end_date,
        'day_of_week' => $day_of_week,
        'start_time' => $start_time,
        'duration' => $duration,
        'rent_ball' => $rent_ball,
        'rent_uniform' => $rent_uniform,
        'note' => $note,
        'total_price' => intval($total_price)
    ];

} else {
    // Single booking - validate and check conflict
    $field_id = $_POST['field_id'] ?? null;
    $booking_date = $_POST['booking_date'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $duration = floatval($_POST['duration'] ?? 1);
    $rent_ball = isset($_POST['rent_ball']) ? 1 : 0;
    $rent_uniform = isset($_POST['rent_uniform']) ? 1 : 0;
    $note = $_POST['note'] ?? '';

    if (!$field_id || !$booking_date || !$start_time) {
        echo 'Dữ liệu đặt sân không hợp lệ'; exit;
    }

    // Không cho đặt nếu khung giờ đã trôi qua (cùng ngày)
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $now = time();
    $start_ts = strtotime($booking_date . ' ' . $start_time);
    if ($booking_date == date('Y-m-d') && $start_ts <= $now) {
        echo 'Khung giờ đã trôi qua. Vui lòng chọn khung giờ khác.'; exit;
    }

    // Check conflict
    $end_timestamp = strtotime($booking_date . ' ' . $start_time) + ($duration * 3600);
    $end_time = date('H:i', $end_timestamp);

    $check_query = "SELECT * FROM bookings WHERE field_id = '$field_id' AND booking_date = '$booking_date' AND status IN ('Chờ xác nhận', 'Đã xác nhận') AND ((start_time <= '$start_time' AND end_time > '$start_time') OR (start_time < '$end_time' AND end_time >= '$end_time') OR (start_time >= '$start_time' AND end_time <= '$end_time'))";
    $check_booking = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_booking) > 0) {
        echo "Sân đã được đặt trong khung giờ này"; exit;
    }

    // Get field price
    $field_q = mysqli_query($conn, "SELECT * FROM football_fields WHERE id = '$field_id'");
    $field = mysqli_fetch_assoc($field_q);
    $field_price = $field['rental_price'] * $duration;

    // Tính phụ thu giờ cao điểm (16:00-18:00)
    $surcharge_per_hour = 200000;
    $surcharge_hours = 0;
    $start_ts = strtotime($booking_date . ' ' . $start_time);
    for ($i = 0; $i < $duration; $i++) {
        $hour = (int)date('H', $start_ts + $i * 3600);
        if ($hour >= 16 && $hour < 18) {
            $surcharge_hours++;
        }
    }
    $surcharge_amount = $surcharge_hours * $surcharge_per_hour;

    $total_price = $field_price + ($rent_ball ? 100000 : 0) + ($rent_uniform ? 100000 : 0) + $surcharge_amount;

    $bookingPayload = [
        'type' => 'single',
        'field_id' => intval($field_id),
        'booking_date' => $booking_date,
        'start_time' => $start_time,
        'duration' => $duration,
        'rent_ball' => $rent_ball,
        'rent_uniform' => $rent_uniform,
        'note' => $note,
        'total_price' => intval($total_price),
        'peak_surcharge' => intval($surcharge_amount)
    ];
}

// Prepare extraData for MoMo: include booking payload and user id (do not create booking yet)
$extraDataArr = ['booking' => $bookingPayload, 'user_id' => $user_id];
$extraData = base64_encode(json_encode($extraDataArr));

$orderInfo = "Thanh toán đặt cọc (MOMO)";
$requestId = time() . rand(100,999);
$requestType = "captureWallet";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
// Build base URL including subdirectory if the app is not at the domain root
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$baseUrl = $protocol . '://' . $host . ($basePath ? $basePath . '/' : '/');
$redirectUrl = $baseUrl . 'momo_post.php';
$ipnUrl = $baseUrl . 'momo_post.php';

$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;

$signature = hash_hmac('sha256', $rawHash, $secretKey);

$data = array(
    'partnerCode' => $partnerCode,
    'partnerName' => "FootballBooking",
    'storeId' => "FBStore",
    'requestId' => $requestId,
    'amount' => (string)$amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'lang' => 'vi',
    'extraData' => $extraData,
    'requestType' => $requestType,
    'signature' => $signature
);

$result = execPostRequest($endpoint, json_encode($data));
if ($result === false) {
    echo "Lỗi khi gọi API MoMo"; exit;
}

$jsonResult = json_decode($result, true);
if (isset($jsonResult['payUrl'])) {
    header('Location: ' . $jsonResult['payUrl']);
    exit;
}

echo "Thanh toán MoMo không thành công";
