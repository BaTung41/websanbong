<?php
session_start();
require 'database/DBController.php';
require 'database/Momo.php';

$momo = new Momo($conn);

// Xử lý callback/redirect MoMo (GET từ redirect hoặc POST từ IPN)
// Thêm logging để debug khi MoMo redirect về gây lỗi
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0777, true);
}
function momo_log($msg) {
    global $logDir;
    $time = date('Y-m-d H:i:s');
    @file_put_contents($logDir . '/momo_post.log', "[$time] $msg\n", FILE_APPEND);
}

$method = $_SERVER['REQUEST_METHOD'];
$params = [];
momo_log("Incoming request method: $method; GET:" . json_encode($_GET) . "; POST:" . json_encode($_POST));
if ($method === 'GET') {
    $params = $_GET;
} elseif ($method === 'POST') {
    if (!empty($_POST)) {
        $params = $_POST;
    } else {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $params = $json;
        }
    }
} else {
    http_response_code(405);
    echo 'Method Not Allowed';
    momo_log('Unsupported method: ' . $method);
    exit;
}

// Lấy các tham số quan trọng (sử dụng isset để tránh NOTICE)
$resultCode = isset($params['resultCode']) ? intval($params['resultCode']) : (isset($params['result']) ? intval($params['result']) : null);
$orderId = isset($params['orderId']) ? trim($params['orderId']) : null;
$amount = isset($params['amount']) ? intval($params['amount']) : 0;
$extraData = isset($params['extraData']) ? $params['extraData'] : '';
momo_log('Parsed params: ' . json_encode(['resultCode'=>$resultCode,'orderId'=>$orderId,'amount'=>$amount,'extraData'=>$extraData]));

// Cố gắng phân giải booking_id từ extraData (nếu sender gửi) hoặc tìm theo momo_order_id
$booking_id = null;
$is_recurring = false;
$user_from_extra = null;
if (!empty($extraData)) {
    $decoded = @base64_decode($extraData);
    $bookingInfo = @json_decode($decoded, true);
    momo_log('Decoded extraData: ' . json_encode($bookingInfo));
    if (json_last_error() === JSON_ERROR_NONE) {
        if (!empty($bookingInfo['booking_id'])) {
            $booking_id = intval($bookingInfo['booking_id']);
        }
        if (!empty($bookingInfo['user_id'])) {
            $user_from_extra = intval($bookingInfo['user_id']);
        }
    }
}

if (!$booking_id && $orderId) {
    // An toàn: sử dụng mysqli_real_escape_string để xây query
    $safeOrder = mysqli_real_escape_string($conn, $orderId);

    // Tìm trong bảng `bookings`
    $res = mysqli_query($conn, "SELECT id FROM bookings WHERE momo_order_id = '$safeOrder' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $booking_id = intval($row['id']);
        momo_log('Found booking by momo_order_id in bookings: ' . $booking_id);
    } else {
        // Không tìm thấy => thử recurring_bookings
        $res2 = mysqli_query($conn, "SELECT id FROM recurring_bookings WHERE momo_order_id = '$safeOrder' LIMIT 1");
        if ($res2 && mysqli_num_rows($res2) > 0) {
            $row = mysqli_fetch_assoc($res2);
            $booking_id = intval($row['id']);
            $is_recurring = true;
            momo_log('Found booking by momo_order_id in recurring_bookings: ' . $booking_id);
        } else {
            momo_log('No booking found by momo_order_id: ' . $safeOrder);
        }
    }
}

// Nếu thanh toán thành công (Momo trả resultCode = 0)
// MoMo sandbox/production returns resultCode == 0 on success. Treat other codes (e.g. 1006) as cancellation/failure.
if ($resultCode !== null && $resultCode === 0) {
    // Resolve user id: ưu tiên session, fallback về extraData nếu MoMo gửi
    $user_id = $_SESSION['user_id'] ?? ($user_from_extra ?? 0);
    momo_log('Payment success for order ' . ($orderId ?? '') . ' ; resolved user_id=' . $user_id . ' ; booking_id=' . $booking_id);

    // Nếu chưa có booking_id, nhưng MoMo gửi payload trong extraData, tạo booking bây giờ (chỉ sau khi MoMo confirm thành công)
    if (empty($booking_id) && !empty($bookingInfo['booking'])) {
        $payload = $bookingInfo['booking'];
        if ($payload['type'] === 'single') {
            // Validate required
            $field_id = intval($payload['field_id']);
            $booking_date = $payload['booking_date'];
            $start_time = $payload['start_time'];
            $duration = floatval($payload['duration']);
            $rent_ball = !empty($payload['rent_ball']) ? 1 : 0;
            $rent_uniform = !empty($payload['rent_uniform']) ? 1 : 0;
            $note = $payload['note'] ?? '';

            // Ngăn việc tạo booking nếu khung giờ đã trôi qua (cùng ngày)
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $now = time();
            $start_ts = strtotime($booking_date . ' ' . $start_time);
            if ($booking_date == date('Y-m-d') && $start_ts <= $now) {
                momo_log('Attempt to create booking for expired slot: ' . $booking_date . ' ' . $start_time);
                if ($method === 'POST') {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Khung giờ đã trôi qua khi xác nhận thanh toán.']);
                    exit;
                } else {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    $baseUrl = $protocol . '://' . $host . ($basePath ? $basePath . '/' : '/');
                    header('Location: ' . $baseUrl . 'my-bookings.php?error=' . urlencode('Khung giờ đã trôi qua khi xác nhận thanh toán. Vui lòng chọn khung giờ khác.'));
                    exit;
                }
            }

            // Conflict check
            $end_timestamp = strtotime($booking_date . ' ' . $start_time) + ($duration * 3600);
            $end_time = date('H:i', $end_timestamp);
            $check_q = sprintf("SELECT id FROM bookings WHERE field_id = %d AND booking_date = '%s' AND status IN ('Chờ xác nhận', 'Đã xác nhận') AND ((start_time <= '%s' AND end_time > '%s') OR (start_time < '%s' AND end_time >= '%s') OR (start_time >= '%s' AND end_time <= '%s')) LIMIT 1", $field_id, $booking_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
            $chk = mysqli_query($conn, $check_q);
            if ($chk && mysqli_num_rows($chk) > 0) {
                momo_log('Conflict found when attempting to create booking after payment.');
                // Redirect user to my-bookings with error
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $baseUrl = $protocol . '://' . $host . ($basePath ? $basePath . '/' : '/');
                header('Location: ' . $baseUrl . 'my-bookings.php?error=' . urlencode('Đã có đơn trùng khung giờ, vui lòng liên hệ Admin.'));
                exit;
            }

            // Use total_price from payload if provided (preferred), otherwise compute (including peak surcharge)
            if (!empty($payload['total_price']) && intval($payload['total_price']) > 0) {
                $total_price = intval($payload['total_price']);
            } else {
                $field_q = mysqli_query($conn, "SELECT * FROM football_fields WHERE id = '$field_id'");
                $field = mysqli_fetch_assoc($field_q);
                $field_price = $field['rental_price'] * $duration;
                $total_price = $field_price + ($rent_ball ? 100000 : 0) + ($rent_uniform ? 100000 : 0);

                // Compute peak surcharge (16:00-18:00)
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
                $total_price += $surcharge_amount;
            }
            $deposit = $amount;

            // Insert booking (paid via MoMo) using escaped query to avoid bind type issues
            $end_time = date('H:i', strtotime($booking_date . ' ' . $start_time) + ($duration * 3600));
            $momo_order = mysqli_real_escape_string($conn, $orderId);
            $userEsc = intval($user_id);
            $fieldEsc = intval($field_id);
            $bookingDateEsc = mysqli_real_escape_string($conn, $booking_date);
            $startTimeEsc = mysqli_real_escape_string($conn, $start_time);
            $endTimeEsc = mysqli_real_escape_string($conn, $end_time);
            $durationEsc = floatval($duration);
            $fieldPriceEsc = floatval($field_price);
            $rentBallEsc = intval($rent_ball);
            $rentUniformEsc = intval($rent_uniform);
            $totalPriceEsc = intval($total_price);
            $noteEsc = mysqli_real_escape_string($conn, $note);
            $depositEsc = intval($deposit);

            $insertSql = "INSERT INTO bookings (user_id, field_id, booking_date, start_time, end_time, duration, field_price, rent_ball, rent_uniform, total_price, note, status, payment_method, deposit_amount, payment_status, momo_order_id) VALUES ($userEsc, $fieldEsc, '$bookingDateEsc', '$startTimeEsc', '$endTimeEsc', $durationEsc, $fieldPriceEsc, $rentBallEsc, $rentUniformEsc, $totalPriceEsc, '$noteEsc', 'Chờ xác nhận', 'momo', $depositEsc, 'Chờ xác nhận', '$momo_order')";
            if (mysqli_query($conn, $insertSql)) {
                $booking_id = mysqli_insert_id($conn);
            } else {
                momo_log('Insert booking failed: ' . mysqli_error($conn));
            }

        } elseif ($payload['type'] === 'recurring') {
            // Recurring booking insert
            $field_id = intval($payload['field_id']);
            $start_date = $payload['start_date'];
            $end_date = $payload['end_date'];
            $day_of_week = $payload['day_of_week'];
            $start_time = $payload['start_time'];
            $duration = floatval($payload['duration']);
            $rent_ball = !empty($payload['rent_ball']) ? 1 : 0;
            $rent_uniform = !empty($payload['rent_uniform']) ? 1 : 0;
            $note = $payload['note'] ?? '';
            $total_price = intval($payload['total_price'] ?? 0);

            // Insert recurring booking using escaped SQL
            $userEsc = intval($user_id);
            $fieldEsc = intval($field_id);
            $startDateEsc = mysqli_real_escape_string($conn, $start_date);
            $endDateEsc = mysqli_real_escape_string($conn, $end_date);
            $dayOfWeekEsc = mysqli_real_escape_string($conn, $day_of_week);
            $startTimeEsc = mysqli_real_escape_string($conn, $start_time);
            $durationEsc = floatval($duration);
            $rentBallEsc = intval($rent_ball);
            $rentUniformEsc = intval($rent_uniform);
            $noteEsc = mysqli_real_escape_string($conn, $note);
            $totalPriceEsc = intval($total_price);
            $momoOrderEsc = mysqli_real_escape_string($conn, $orderId);

            $insertSql = "INSERT INTO recurring_bookings (user_id, field_id, start_date, end_date, day_of_week, start_time, duration, rent_ball, rent_uniform, note, total_price, status, payment_method, momo_order_id) VALUES ($userEsc, $fieldEsc, '$startDateEsc', '$endDateEsc', '$dayOfWeekEsc', '$startTimeEsc', $durationEsc, $rentBallEsc, $rentUniformEsc, '$noteEsc', $totalPriceEsc, 'Chờ xác nhận', 'momo', '$momoOrderEsc')";
            if (mysqli_query($conn, $insertSql)) {
                $booking_id = mysqli_insert_id($conn);
                $is_recurring = true;
            } else {
                momo_log('Insert recurring booking failed: ' . mysqli_error($conn));
            }
        }
    }

    // Nếu có booking_id, cập nhật momo_order_id nếu chưa có và set trạng thái "Chờ xác nhận"
    if (!empty($booking_id)) {
        // Cập nhật momo_order_id nếu cần
        if (!empty($orderId)) {
            $safeOrder = mysqli_real_escape_string($conn, $orderId);
            $q = "UPDATE bookings SET momo_order_id = '$safeOrder' WHERE id = " . intval($booking_id) . " AND (momo_order_id IS NULL OR momo_order_id = '')";
            @mysqli_query($conn, $q);
            if (mysqli_error($conn)) momo_log('Error setting momo_order_id on bookings: ' . mysqli_error($conn));

            $q2 = "UPDATE recurring_bookings SET momo_order_id = '$safeOrder' WHERE id = " . intval($booking_id) . " AND (momo_order_id IS NULL OR momo_order_id = '')";
            @mysqli_query($conn, $q2);
            if (mysqli_error($conn)) momo_log('Error setting momo_order_id on recurring_bookings: ' . mysqli_error($conn));
        }

        // Cập nhật trạng thái
        if ($is_recurring) {
            $stmt = $conn->prepare("UPDATE recurring_bookings SET payment_status = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                $status = 'Chờ xác nhận';
                $stmt->bind_param('si', $status, $booking_id);
                $stmt->execute();
                if ($stmt->error) momo_log('Update recurring_bookings error: ' . $stmt->error);
                $stmt->close();
            } else {
                momo_log('Prepare failed for recurring update: ' . mysqli_error($conn));
            }
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                $status = 'Chờ xác nhận';
                $stmt->bind_param('si', $status, $booking_id);
                $stmt->execute();
                if ($stmt->error) momo_log('Update bookings error: ' . $stmt->error);
                $stmt->close();
            } else {
                momo_log('Prepare failed for bookings update: ' . mysqli_error($conn));
            }
        }
    } else {
        momo_log('No booking ID to update after successful payment.');
    }

    // Lưu thông tin MoMo vào bảng `momos` (status = 1 là thành công)
    $stored = $momo->storeMomoInfo($user_id, $booking_id ?? 0, $orderId ?? '', $amount, 1, json_encode($params));
    if (!$stored) momo_log('Failed to store momo info for order ' . ($orderId ?? ''));

    // Nếu đây là IPN (POST) trả về 200 OK theo spec
    if ($method === 'POST') {
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        momo_log('Responded to IPN OK');
        exit;
    }

    // Redirect người dùng về trang quản lý đặt sân với thông báo thành công
    momo_log('Redirecting user to my-bookings after success.');
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $baseUrl = $protocol . '://' . $host . ($basePath ? $basePath . '/' : '/');
    header('Location: ' . $baseUrl . 'my-bookings.php?success=' . urlencode('Thanh toán MoMo thành công. Đơn đang chờ xác nhận.'));
    exit;
}

// Không phải thành công: trả lỗi phù hợp (bao gồm hủy bởi người dùng)
$msg = 'Lỗi trong quá trình thanh toán MoMo';
if ($resultCode !== null) {
    // 1006 thường là hủy giao dịch / từ chối bởi người dùng trong sandbox
    $msg = 'Thanh toán không thành công (mã: ' . htmlspecialchars($resultCode) . ')';
}

// Lưu thông tin MoMo với trạng thái thất bại (0)
$resolved_user_id = $_SESSION['user_id'] ?? ($user_from_extra ?? 0);
$storedFail = $momo->storeMomoInfo($resolved_user_id, $booking_id ?? 0, $orderId ?? '', $amount, 0, json_encode($params));
if (!$storedFail) {
    momo_log('Failed to store momo info for failed order ' . ($orderId ?? '')); 
} else {
    momo_log('Stored momo failure record for order ' . ($orderId ?? ''));
}

if ($method === 'POST') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// Redirect về my-bookings với lỗi (tránh đưa user về index vô nghĩa)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$baseUrl = $protocol . '://' . $host . ($basePath ? $basePath . '/' : '/');
header('Location: ' . $baseUrl . 'my-bookings.php?error=' . urlencode($msg));
exit;
