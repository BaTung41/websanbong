<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_HashSecret = "BPGTMEF4IA6HNU9EUMQVEEZI1HZ5LHBE";

$vnp_SecureHash = $_GET['vnp_SecureHash'];
unset($_GET['vnp_SecureHash']);
unset($_GET['vnp_SecureHashType']);

ksort($_GET);

$hashData = "";
$i = 0;

foreach ($_GET as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

if ($secureHash == $vnp_SecureHash) {

    if ($_GET['vnp_ResponseCode'] == '00') {
        echo "<h2>Thanh toán thành công</h2>";
        echo "Mã đơn hàng: " . $_GET['vnp_TxnRef'] . "<br>";
        echo "Số tiền: " . ($_GET['vnp_Amount'] / 100) . " VND<br>";
        echo "Nội dung: " . $_GET['vnp_OrderInfo'];
    } else {
        echo "<h2>Thanh toán thất bại</h2>";
        echo "Mã lỗi: " . $_GET['vnp_ResponseCode'];
    }

} else {
    echo "Sai chữ ký bảo mật";
}

if ($secureHash === $vnp_SecureHash) {
    if ($_GET['vnp_ResponseCode'] === '00') {
        header("Location: /payment_result.php?status=success");
    } else {
        header("Location: /payment_result.php?status=fail");
    }
    exit;
}

echo "Sai chữ ký";
