<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$tongtien = $_POST['sotien'];

// Validate booking slot not in the past when booking for today (if booking info provided)
date_default_timezone_set('Asia/Ho_Chi_Minh');
$booking_date = $_POST['booking_date'] ?? null;
$start_time = $_POST['start_time'] ?? null;
if ($booking_date && $start_time) {
    $now = time();
    $start_ts = strtotime($booking_date . ' ' . $start_time);
    if ($booking_date == date('Y-m-d') && $start_ts <= $now) {
        die('Khung giờ đã trôi qua. Vui lòng chọn khung giờ khác.');
    }
}

$vnp_TmnCode = "JU31M94R"; // Website ID in VNPAY System
$vnp_HashSecret = "BPGTMEF4IA6HNU9EUMQVEEZI1HZ5LHBE"; // Secret key
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "https://4398166edcf1.ngrok-free.app //my_bookings.php";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";

// Thời gian hết hạn
$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

// Tạo đơn hàng
$vnp_TxnRef = time() . "";
$vnp_OrderInfo = 'Thanh toán đơn hàng đặt tại web';
$vnp_OrderType = 'billpayment';

$vnp_Amount = $tongtien * 100;
$vnp_Locale = 'vn';

$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

$vnp_ExpireDate = $expire;

// Dữ liệu gửi sang VNPAY
$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => $startTime,
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef,
    "vnp_ExpireDate" => $vnp_ExpireDate,
);

// Sắp xếp
ksort($inputData);

$query = "";
$hashdata = "";
$i = 0;

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

// Tạo chữ ký
$vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

// Redirect
$vnp_Url = $vnp_Url . "?" . $query . "vnp_SecureHash=" . $vnp_SecureHash;
header('Location: ' . $vnp_Url);
die();
