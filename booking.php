<?php
include 'database/DBController.php';
session_start();

$user_id = @$_SESSION['user_id'] ?? null;
$field_id = $_GET['field_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit();
}

if (!$field_id) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin sân bóng
$field_query = mysqli_query($conn, "SELECT * FROM football_fields WHERE id = '$field_id'") or die('Query failed');
$field = mysqli_fetch_assoc($field_query);

// Xử lý đặt sân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Xử lý đặt sân (Upload ảnh là tuỳ chọn — nếu có thì lưu, không có thì để trống)
    $image_name = '';
    if (isset($_FILES['payment_image']) && $_FILES['payment_image']['error'] === 0) {
        $image = $_FILES['payment_image'];
        $image_name = time() . '_' . $image['name'];
        $target_path = 'assets/bill/' . $image_name;
        if (!file_exists('assets/bill')) {
            mkdir('assets/bill', 0777, true);
        }
        if (!move_uploaded_file($image['tmp_name'], $target_path)) {
            echo "<script>
                alert('Có lỗi khi upload ảnh. Vui lòng thử lại!');
                history.back();
            </script>";
            exit();
        }
    }

    // Tiếp tục xử lý insert booking
    $user_id = $_SESSION['user_id'];
    $field_id = $_POST['field_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $duration = floatval($_POST['duration']);
    $rent_ball = isset($_POST['rent_ball']) ? 1 : 0;
    $rent_uniform = isset($_POST['rent_uniform']) ? 1 : 0;
    $payment_method = $_POST['payment_method'];
    $note = $_POST['note'] ?? '';

        // Tính thời gian kết thúc
        $start_timestamp = strtotime("$booking_date $start_time");
        $duration_seconds = $duration * 3600;
        $end_timestamp = $start_timestamp + $duration_seconds;
        $end_time = date('H:i', $end_timestamp);

        // Ngăn việc đặt nếu khung giờ đã trôi qua trong cùng ngày
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        if ($booking_date == date('Y-m-d') && $start_timestamp <= time()) {
            $error_message = 'Khung giờ đã trôi qua. Vui lòng chọn khung giờ khác!';
        } else {
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

            if(mysqli_num_rows($check_booking) > 0) {
                $existing_booking = mysqli_fetch_assoc($check_booking);
                $error_message = "Sân " . $existing_booking['field_name'] . " đã được đặt trong khung giờ " . 
                                $existing_booking['start_time'] . " - " . $existing_booking['end_time'] . 
                                " ngày " . date('d/m/Y', strtotime($existing_booking['booking_date'])) . 
                                ". Vui lòng chọn khung giờ khác!";
            } else {
                // Nếu không trùng lịch thì tiếp tục xử lý và insert
                $field_query = mysqli_query($conn, "SELECT * FROM football_fields WHERE id = '$field_id'");
                $field = mysqli_fetch_assoc($field_query);
                $field_price = $field['rental_price'] * $duration;
                $total_price = $field_price;
                if ($rent_ball) $total_price += 100000;
                if ($rent_uniform) $total_price += 100000;

                // Tính phụ thu giờ cao điểm (16:00-18:00) 200.000 đ / giờ
                $surcharge_per_hour = 200000;
                $surcharge_hours = 0;
                for ($i = 0; $i < $duration; $i++) {
                    $hour = (int)date('H', $start_timestamp + $i * 3600);
                    if ($hour >= 16 && $hour < 18) {
                        $surcharge_hours++;
                    }
                }
                $surcharge_amount = $surcharge_hours * $surcharge_per_hour;
                $total_price += $surcharge_amount;

                $deposit_amount = $total_price * 0.5;

                // Thêm tên file ảnh vào câu query insert
                $insert_query = "INSERT INTO bookings 
                    (user_id, field_id, booking_date, start_time, end_time, duration,
                     field_price, rent_ball, rent_uniform, total_price, note, status,
                     payment_method, deposit_amount, payment_status, payment_image) 
                    VALUES (
                        '$user_id', '$field_id', '$booking_date', '$start_time', '$end_time',
                        '$duration', '$field_price', '$rent_ball', '$rent_uniform', '$total_price',
                        '$note', 'Chờ xác nhận', '$payment_method', '$deposit_amount', 'Đã đặt cọc',
                        '$image_name'
                    )";

                if (mysqli_query($conn, $insert_query)) {
                    echo "<script>
                        alert('Đặt sân thành công!');
                        window.location.href = 'my-bookings.php';
                    </script>";
                    exit();
                } else {
                    $error_message = "Có lỗi xảy ra khi đặt sân. Vui lòng thử lại!";
                    // Xóa file ảnh nếu insert thất bại
                    if (!empty($image_name) && file_exists($target_path)) {
                        unlink($target_path);
                    }
                }
            }
        }
}

?>

<?php include 'header.php'; ?>
<?php
// Display messages coming from VNPay return
if (!empty($_GET['success'])) {
    echo '<div class="container mt-3"><div class="alert alert-success" role="alert">' . htmlspecialchars($_GET['success']) . '</div></div>';
} elseif (!empty($_GET['error'])) {
    echo '<div class="container mt-3"><div class="alert alert-danger" role="alert">' . htmlspecialchars($_GET['error']) . '</div></div>';
}
?>
<!-- Trong phần head -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Thêm jQuery trước Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Trong header.php -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="booking-container py-5">
    <div class="container">
        <div class="row">
            <!-- Thông tin sân -->
            <div class="col-lg-4 mb-4">
                <div class="field-info-card">
                    <img src="assets/fields/<?php echo $field['image']; ?>" alt="<?php echo $field['name']; ?>" class="img-fluid mb-3">
                    <h3><?php echo $field['name']; ?></h3>
                    <div class="field-details">
                        <p><i class="fas fa-futbol"></i> Sân <?php echo $field['field_type']; ?> người</p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo $field['address']; ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo $field['phone_number']; ?></p>
                        <p class="price"><i class="fas fa-money-bill"></i> 
                            <?php echo number_format($field['rental_price'], 0, ',', '.'); ?> đ/giờ
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form đặt sân -->
            <div class="col-lg-8">
                <div class="booking-form-card">
                    <h2 class="form-title">Đặt Sân</h2>
                    <div class="col-12">
                        <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" id="bookingForm">
                        <input type="hidden" name="field_id" value="<?php echo $field_id; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Ngày đặt sân</label>
                                <input type="date" name="booking_date" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Giờ bắt đầu & Khung giờ</label>
                                <div class="custom-time-picker">
                                    <button type="button" class="btn btn-outline-secondary w-100 text-start time-picker-btn" id="timePickerBtn">
                                        <span class="time-picker-value">Chọn khung giờ</span>
                                        <i class="fas fa-chevron-down float-end"></i>
                                    </button>
                                    <div class="time-picker-dropdown" id="timePickerDropdown">
                                        <div class="time-picker-list">
                                            <div class="time-option-checkbox" data-value="06:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="06:00">
                                                <span>06:00 - 07:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="07:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="07:00">
                                                <span>07:00 - 08:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="08:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="08:00">
                                                <span>08:00 - 09:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="09:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="09:00">
                                                <span>09:00 - 10:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="10:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="10:00">
                                                <span>10:00 - 11:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="11:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="11:00">
                                                <span>11:00 - 12:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="12:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="12:00">
                                                <span>12:00 - 13:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="13:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="13:00">
                                                <span>13:00 - 14:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="14:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="14:00">
                                                <span>14:00 - 15:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="15:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="15:00">
                                                <span>15:00 - 16:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="16:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="16:00">
                                                <span>16:00 - 17:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="17:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="17:00">
                                                <span>17:00 - 18:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="18:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="18:00">
                                                <span>18:00 - 19:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="19:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="19:00">
                                                <span>19:00 - 20:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="20:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="20:00">
                                                <span>20:00 - 21:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="21:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="21:00">
                                                <span>21:00 - 22:00</span>
                                            </div>
                                            <div class="time-option-checkbox" data-value="22:00">
                                                <input type="checkbox" class="time-slot-checkbox" value="22:00">
                                                <span>22:00 - 23:00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="start_time" id="start_time_hidden" required>
                                <input type="hidden" name="duration" id="duration_hidden" value="1">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Dịch vụ thêm</label>
                                <div class="additional-services">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="rent_ball" id="rentBall" value="1">
                                        <label class="form-check-label" for="rentBall">
                                            Thuê bóng (+100.000đ)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="rent_uniform" id="rentUniform" value="1">
                                        <label class="form-check-label" for="rentUniform">
                                            Thuê áo pitch (+100.000đ)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="price-summary">
                                    <div class="price-item">
                                        <span>Tiền sân:</span>
                                        <span id="fieldPrice">0 đ</span>
                                    </div>
                                    <div class="price-item" id="ballPriceRow" style="display: none;">
                                        <span>Thuê bóng:</span>
                                        <span>100.000 đ</span>
                                    </div>
                                    <div class="price-item" id="uniformPriceRow" style="display: none;">
                                        <span>Thuê áo pitch:</span>
                                        <span>100.000 đ</span>
                                    </div>
                                    <div class="price-item" id="peakPriceRow" style="display: none;">
                                        <span>Giờ cao điểm (16:00-18:00):</span>
                                        <span class="peak-amount">0 đ</span>
                                    </div>
                                    <div class="price-item total">
                                        <span>Tổng tiền:</span>
                                        <span id="totalPrice">0 đ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Ghi chú</label>
                            <textarea name="note" class="form-control" rows="3" 
                                    placeholder="Nhập ghi chú nếu có..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Phương thức thanh toán</label>
                            <div class="payment-methods">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="momo" value="momo" required>
                                    <label class="form-check-label payment-label" for="momo">
                                        <img src="assets/momo.png" alt="MoMo">
                                        MOMO
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="vnpay" value="vnpay" required>
                                    <label class="form-check-label payment-label" for="vnpay">
                                        <img src="assets/vnpay.png" alt="VNPay">
                                        VNPay
                                    </label>
                                </div>
                                <!-- Bank transfer option removed -->
                            </div>
                        </div>
                        <button type="button" class="btn btn-booking mt-4" onclick="showPaymentModal()">
                            Đặt sân
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal thanh toán với form riêng -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Thanh toán đặt cọc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Copy tất cả hidden fields từ form chính -->
                    <input type="hidden" name="field_id" id="modal_field_id">
                    <input type="hidden" name="booking_date" id="modal_booking_date">
                    <input type="hidden" name="start_time" id="modal_start_time">
                    <input type="hidden" name="duration" id="modal_duration">
                    <input type="hidden" name="rent_ball" id="modal_rent_ball">
                    <input type="hidden" name="rent_uniform" id="modal_rent_uniform">
                    <input type="hidden" name="note" id="modal_note">
                    <input type="hidden" name="payment_method" id="modal_payment_method">
                    
                    <div class="payment-info">
                        <div class="amount-info mb-4">
                            <h6>Thông tin thanh toán:</h6>
                            <p>Tổng tiền: <span id="modalTotalPrice">0 đ</span></p>
                            <p>Số tiền đặt cọc (50%): <span id="modalDepositAmount">0 đ</span></p>
                        </div>

                        <!-- Phần hiển thị phương thức thanh toán -->
                        <div id="momoPayment" style="display: none;">
                            <h6>Thanh toán QR MOMO</h6>
                            <div class="text-center">
                                <button type="button" class="btn btn-danger thanhtoan" onclick="submitMomoPayment()">Thanh toán QR MOMO</button>
                            </div>
                        </div>

                        <div id="vnpayPayment" style="display: none;">
                            <h6>Thanh toán QR VNPay</h6>
                            <div class="text-center">
                                <button type="button" class="btn btn-danger thanhtoan" onclick="submitVnpayPayment()">Thanh toán QR VNPay</button>
                            </div>
                        </div>

                        <!-- Bank transfer details removed -->

                        <!-- Phần thanh toán MOMO: nút gửi tới confirm_momo.php -->

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.booking-container {
    background: #f8f9fa;
    min-height: 100vh;
}

/* Custom time picker styles */
.custom-time-picker {
    position: relative;
}

.time-picker-btn {
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s;
}

.time-picker-btn:hover {
    border-color: #28a745;
    background-color: #f8f9fa;
}

.time-picker-btn.active {
    border-color: #28a745;
    background-color: #fff;
}

.time-picker-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 8px 8px;
    z-index: 1000;
    margin-top: -1px;
}

.time-picker-dropdown.show {
    display: block;
}

.time-picker-list {
    max-height: 300px;
    overflow-y: auto;
}

.time-option-checkbox {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.time-option-checkbox:hover {
    background-color: #f8f9fa;
}

.time-option-checkbox input[type="checkbox"] {
    cursor: pointer;
    margin: 0;
}

.time-option-checkbox.disabled {
    cursor: not-allowed;
    opacity: 0.5;
    color: #999;
    background-color: #f5f5f5;
}

.time-option-checkbox.disabled:hover {
    background-color: #f5f5f5;
}

.time-option-checkbox.disabled input[type="checkbox"] {
    cursor: not-allowed;
}

.time-option-checkbox:last-child {
    border-bottom: none;
}

.time-option {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s;
}

.time-option:hover {
    background-color: #f8f9fa;
    color: #28a745;
}

.time-option.selected {
    background-color: #e8f5e9;
    color: #28a745;
    font-weight: 500;
}

.time-option:last-child {
    border-bottom: none;
}

.time-option.disabled {
    cursor: not-allowed;
    opacity: 0.5;
    color: #999;
    background-color: #f5f5f5;
}

.time-option.disabled:hover {
    background-color: #f5f5f5;
    color: #999;
}

.field-info-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.field-info-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
}

.field-info-card h3 {
    color: #1B4D3E;
    margin: 15px 0;
}

.field-details p {
    margin-bottom: 10px;
    color: #666;
}

.field-details i {
    width: 25px;
    color: #28a745;
}

.price {
    font-size: 18px;
    color: #28a745;
    font-weight: 600;
}

.booking-form-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.form-title {
    color: #1B4D3E;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #28a745;
}

.form-control {
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.total-price {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    color: #28a745;
}

.btn-booking {
    background: #28a745;
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-booking:hover {
    background: #218838;
    transform: translateY(-2px);
}

label {
    color: #666;
    margin-bottom: 8px;
}

.additional-services {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.form-check {
    margin-bottom: 10px;
}

.form-check:last-child {
    margin-bottom: 0;
}

.form-check-input {
    cursor: pointer;
}

.form-check-label {
    cursor: pointer;
    color: #666;
}

.price-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.price-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #ddd;
}

.price-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.price-item.total {
    font-weight: 600;
    color: #28a745;
    font-size: 18px;
    border-top: 2px solid #ddd;
    margin-top: 10px;
    padding-top: 10px;
}

.payment-methods {
    display: flex;
    gap: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.payment-label {
    display: flex;
    align-items: center;
    gap: 10px;
}

.payment-label img {
    height: 30px;
    width: auto;
}

    /* Bank transfer styles removed */

.amount-info {
    background: #e9ecef;
    padding: 15px;
    border-radius: 8px;
}

.amount-info p {
    margin-bottom: 5px;
    font-size: 16px;
}

.amount-info p:last-child {
    color: #28a745;
    font-weight: bold;
}

.modal-header .btn-close {
    opacity: 1;
    padding: 0.5rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
}

.modal-header .btn-close:hover {
    opacity: 0.75;
}

.modal-footer .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

.modal-footer .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}
</style>

<script>
function validateBooking() {
    const startTimeInput = document.querySelector('input[name="start_time"]');
    const durationInput = document.getElementById('duration_hidden');
    
    const startTime = startTimeInput.value;
    const duration = parseFloat(durationInput.value) || 1;
    
    // Kiểm tra có chọn giờ không
    if (!startTime) {
        alert('Vui lòng chọn khung giờ');
        return false;
    }
    
    // Chuyển start_time sang timestamp
    const [hours, minutes] = startTime.split(':');
    const startHour = parseInt(hours);
    const endHour = startHour + Math.floor(duration);
    const endMinutes = minutes === '30' ? (duration % 1) * 60 + 30 : (duration % 1) * 60;
    
    // Kiểm tra thời gian đặt sân
    if (startHour < 6 || endHour > 22 || (endHour === 22 && endMinutes > 0)) {
        alert('Thời gian đặt sân phải từ 6:00 đến 22:00');
        return false;
    }

    // Nếu chọn cùng ngày, không cho chọn khung giờ đã trôi qua
    const bookingDate = document.querySelector('input[name="booking_date"]').value;
    if (bookingDate === new Date().toISOString().slice(0,10)) {
        const [h, m] = startTime.split(':');
        const slot = new Date();
        slot.setHours(parseInt(h, 10), parseInt(m, 10), 0, 0);
        const now = new Date();
        if (slot <= now) {
            alert('Khung giờ đã trôi qua. Vui lòng chọn khung giờ khác.');
            return false;
        }
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật giá khi thay đổi thời gian hoặc dịch vụ
    const form = document.getElementById('bookingForm');
    const durationHidden = document.getElementById('duration_hidden');
    const rentBallCheckbox = document.getElementById('rentBall');
    const rentUniformCheckbox = document.getElementById('rentUniform');
    const fieldPriceSpan = document.getElementById('fieldPrice');
    const totalPriceSpan = document.getElementById('totalPrice');
    const ballPriceRow = document.getElementById('ballPriceRow');
    const uniformPriceRow = document.getElementById('uniformPriceRow');
    const peakPriceRow = document.getElementById('peakPriceRow');
    
    function updatePrice() {
        const duration = parseFloat(durationHidden.value) || 1;
        const rentalPrice = <?php echo $field['rental_price']; ?>;
        const rentBall = rentBallCheckbox.checked;
        const rentUniform = rentUniformCheckbox.checked;
        
        // Tính tiền sân
        const fieldPrice = duration * rentalPrice;
        fieldPriceSpan.textContent = fieldPrice.toLocaleString('vi-VN') + ' đ';
        
        // Hiển thị/ẩn các dòng giá dịch vụ
        ballPriceRow.style.display = rentBall ? 'flex' : 'none';
        uniformPriceRow.style.display = rentUniform ? 'flex' : 'none';
        
        // Tính phụ thu giờ cao điểm (200.000 đ / giờ cho 16:00-18:00)
        const checkedSlots = Array.from(document.querySelectorAll('.time-slot-checkbox'))
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        const surchargePerHour = 200000;
        let surchargeHours = 0;
        checkedSlots.forEach(v => {
            const h = parseInt(v.split(':')[0], 10);
            if (h >= 16 && h < 18) surchargeHours++;
        });
        const peakSurcharge = surchargeHours * surchargePerHour;
        if (peakSurcharge > 0) {
            peakPriceRow.style.display = 'flex';
            peakPriceRow.querySelector('.peak-amount').textContent = peakSurcharge.toLocaleString('vi-VN') + ' đ';
        } else {
            peakPriceRow.style.display = 'none';
        }
        
        // Tính tổng tiền
        let totalPrice = fieldPrice;
        if (rentBall) totalPrice += 100000;
        if (rentUniform) totalPrice += 100000;
        totalPrice += peakSurcharge;
        
        totalPriceSpan.textContent = totalPrice.toLocaleString('vi-VN') + ' đ';
        
        // Cập nhật giá trong modal
        document.getElementById('modalTotalPrice').textContent = totalPrice.toLocaleString('vi-VN') + ' đ';
        document.getElementById('modalDepositAmount').textContent = (totalPrice * 0.5).toLocaleString('vi-VN') + ' đ';
    }
    
    // Gắn sự kiện cho các trường input
    rentBallCheckbox.addEventListener('change', updatePrice);
    rentUniformCheckbox.addEventListener('change', updatePrice);
    
    // Khởi tạo giá ban đầu
    updatePrice();
});

// Custom time picker with multi-slot selection
document.addEventListener('DOMContentLoaded', function() {
    const timePickerBtn = document.getElementById('timePickerBtn');
    const timePickerDropdown = document.getElementById('timePickerDropdown');
    const timeSlotCheckboxes = document.querySelectorAll('.time-slot-checkbox');
    const startTimeHidden = document.getElementById('start_time_hidden');
    const durationHidden = document.getElementById('duration_hidden');
    const timePickerValue = document.querySelector('.time-picker-value');
    const bookingForm = document.getElementById('bookingForm');
    const bookingDateInput = bookingForm.querySelector('[name="booking_date"]');
    
    // Hàm cập nhật display text và hidden fields
    function updateTimeDisplay() {
        const checkedSlots = Array.from(timeSlotCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
        
        if (checkedSlots.length === 0) {
            timePickerValue.textContent = 'Chọn khung giờ';
            startTimeHidden.value = '';
            durationHidden.value = '1';
        } else {
            // Sắp xếp giờ
            checkedSlots.sort();
            
            // Giờ bắt đầu là giờ đầu tiên
            const startTime = checkedSlots[0];
            startTimeHidden.value = startTime;
            
            // Duration = số khung giờ được chọn
            const duration = checkedSlots.length;
            durationHidden.value = duration;
            
            // Nhóm các giờ liên tiếp
            const timeRanges = [];
            let rangeStart = checkedSlots[0];
            let rangeLast = checkedSlots[0];
            
            for (let i = 1; i < checkedSlots.length; i++) {
                const currentHour = parseInt(checkedSlots[i].split(':')[0]);
                const lastHour = parseInt(rangeLast.split(':')[0]);
                
                // Nếu giờ hiện tại liên tiếp với giờ trước
                if (currentHour === lastHour + 1) {
                    rangeLast = checkedSlots[i];
                } else {
                    // Nếu không liên tiếp, thêm range vào mảng
                    const nextHour = String(lastHour + 1).padStart(2, '0') + ':00';
                    timeRanges.push(`${rangeStart} - ${nextHour}`);
                    rangeStart = checkedSlots[i];
                    rangeLast = checkedSlots[i];
                }
            }
            
            // Thêm range cuối cùng
            const lastHour = parseInt(rangeLast.split(':')[0]);
            const lastNextHour = String(lastHour + 1).padStart(2, '0') + ':00';
            timeRanges.push(`${rangeStart} - ${lastNextHour}`);
            
            // Hiển thị tất cả ranges
            const displayText = timeRanges.join(', ');
            timePickerValue.textContent = `${displayText} (${duration} giờ)`;
            
            // Tự động đóng dropdown sau 200ms
            setTimeout(() => {
                timePickerDropdown.classList.remove('show');
                timePickerBtn.classList.remove('active');
            }, 200);
        }
        
        // Update price
        const priceEvent = new Event('change');
        document.getElementById('duration_hidden').dispatchEvent(priceEvent);
        updatePriceDisplay();
    }
    
    function updatePriceDisplay() {
        const event = new Event('change');
        document.getElementById('rentBall').dispatchEvent(event);
    }
    
    // Hàm load giờ đã đặt
    function loadBookedTimes() {
        const selectedDate = bookingDateInput.value;
        
        if (!selectedDate) {
            return;
        }
        
        // Get checked slots to calculate duration
        const checkedSlots = Array.from(timeSlotCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
        const duration = checkedSlots.length || 1;
        
        fetch('get_booked_times.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'field_id=' + bookingForm.querySelector('[name="field_id"]').value + 
                  '&booking_date=' + selectedDate +
                  '&duration=' + duration
        })
        .then(response => response.json())
        .then(data => {
            // Remove disabled class từ tất cả option
            document.querySelectorAll('.time-option-checkbox').forEach(option => {
                option.classList.remove('disabled');
                option.style.pointerEvents = 'auto';
                const checkbox = option.querySelector('.time-slot-checkbox');
                if (checkbox) checkbox.disabled = false;
            });
            
            // Thêm disabled class vào các giờ không available
            if (data.unavailableTimes && data.unavailableTimes.length > 0) {
                data.unavailableTimes.forEach(time => {
                    const option = document.querySelector(`.time-option-checkbox[data-value="${time}"]`);
                    if (option) {
                        option.classList.add('disabled');
                        option.style.pointerEvents = 'none';
                        const checkbox = option.querySelector('.time-slot-checkbox');
                        if (checkbox) {
                            checkbox.disabled = true;
                            checkbox.checked = false;
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Toggle dropdown
    timePickerBtn.addEventListener('click', function(e) {
        e.preventDefault();
        timePickerDropdown.classList.toggle('show');
        timePickerBtn.classList.toggle('active');
    });
    
    // Handle checkbox changes
    timeSlotCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            // Nếu đã disabled thì không cho check
            if (this.disabled) {
                e.preventDefault();
                this.checked = false;
                return;
            }
            
            updateTimeDisplay();
            loadBookedTimes();
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-time-picker')) {
            timePickerDropdown.classList.remove('show');
            timePickerBtn.classList.remove('active');
        }
    });
    
    // Load booked times khi ngày thay đổi
    bookingDateInput.addEventListener('change', loadBookedTimes);
});

// Khởi tạo modal khi trang được load
let paymentModal;
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo modal
    const modalElement = document.getElementById('paymentModal');
    paymentModal = new bootstrap.Modal(modalElement);

    // Xử lý nút đóng và dấu X
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            paymentModal.hide();
        });
    });

    // Xử lý đóng modal khi click ra ngoài
    modalElement.addEventListener('click', function(event) {
        if (event.target === modalElement) {
            paymentModal.hide();
        }
    });

    // Xử lý phím ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modalElement.classList.contains('show')) {
            paymentModal.hide();
        }
    });
});

// Cập nhật hàm showPaymentModal
function showPaymentModal() {
    // Validate form chính trước
    if (!validateBooking()) {
        return;
    }

    // Copy dữ liệu từ form chính sang form modal
    const mainForm = document.getElementById('bookingForm');
    document.getElementById('modal_field_id').value = mainForm.querySelector('[name="field_id"]').value;
    document.getElementById('modal_booking_date').value = mainForm.querySelector('[name="booking_date"]').value;
    document.getElementById('modal_start_time').value = document.getElementById('start_time_hidden').value;
    document.getElementById('modal_duration').value = document.getElementById('duration_hidden').value;
    document.getElementById('modal_rent_ball').value = mainForm.querySelector('[name="rent_ball"]').checked ? 1 : 0;
    document.getElementById('modal_rent_uniform').value = mainForm.querySelector('[name="rent_uniform"]').checked ? 1 : 0;
    document.getElementById('modal_note').value = mainForm.querySelector('[name="note"]').value;
    
    // Lấy phương thức thanh toán đã chọn
    const selectedPayment = mainForm.querySelector('input[name="payment_method"]:checked');
    if (selectedPayment) {
        document.getElementById('modal_payment_method').value = selectedPayment.value;
        // Hiển thị phương thức thanh toán tương ứng
        showPaymentMethod(selectedPayment.value);
    }

    // Hiển thị modal sử dụng biến toàn cục đã khởi tạo
    paymentModal.show();
}



// Hiển thị phương thức thanh toán
function showPaymentMethod(method) {
    ['momoPayment','vnpayPayment'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
    const sel = document.getElementById(method + 'Payment');
    if (sel) sel.style.display = 'block';
}

// Function to submit MoMo payment
function submitMomoPayment() {
    const depositText = document.getElementById('modalDepositAmount').textContent || '0';
    const depositNum = parseInt(depositText.replace(/[^0-9]/g, '')) || 0;
    
    if (depositNum <= 0) {
        alert('Vui lòng chọn khung giờ trước');
        return;
    }
    
    // Populate hidden MoMo form
    document.getElementById('hiddenMomoAmount').value = depositNum;
    document.getElementById('hiddenMomoFieldId').value = document.getElementById('modal_field_id').value;
    document.getElementById('hiddenMomoBookingDate').value = document.getElementById('modal_booking_date').value;
    document.getElementById('hiddenMomoStartTime').value = document.getElementById('modal_start_time').value;
    document.getElementById('hiddenMomoDuration').value = document.getElementById('modal_duration').value;
    document.getElementById('hiddenMomoRentBall').value = document.getElementById('modal_rent_ball').value;
    document.getElementById('hiddenMomoRentUniform').value = document.getElementById('modal_rent_uniform').value;
    document.getElementById('hiddenMomoNote').value = document.getElementById('modal_note').value;
    
    // Submit form
    document.getElementById('hiddenMomoForm').submit();
}

// Function to submit VNPay payment
function submitVnpayPayment() {
    const depositText = document.getElementById('modalDepositAmount').textContent || '0';
    const depositNum = parseInt(depositText.replace(/[^0-9]/g, '')) || 0;
    
    if (depositNum <= 0) {
        alert('Vui lòng chọn khung giờ trước');
        return;
    }
    
    // Populate hidden VNPay form
    document.getElementById('hiddenVnpayAmount').value = depositNum;
    document.getElementById('hiddenVnpayFieldId').value = document.getElementById('modal_field_id').value;
    document.getElementById('hiddenVnpayBookingDate').value = document.getElementById('modal_booking_date').value;
    document.getElementById('hiddenVnpayStartTime').value = document.getElementById('modal_start_time').value;
    document.getElementById('hiddenVnpayDuration').value = document.getElementById('modal_duration').value;
    document.getElementById('hiddenVnpayRentBall').value = document.getElementById('modal_rent_ball').value;
    document.getElementById('hiddenVnpayRentUniform').value = document.getElementById('modal_rent_uniform').value;
    document.getElementById('hiddenVnpayNote').value = document.getElementById('modal_note').value;
    document.getElementById('hiddenVnpayTotalPrice').value = document.getElementById('modalTotalPrice').textContent.replace(/[^\d]/g, '');
    
    // Submit form
    document.getElementById('hiddenVnpayForm').submit();
}

</script>

<!-- Hidden MoMo Payment Form -->
<form action="confirm_momo.php" method="post" id="hiddenMomoForm" style="display: none;">
    <input type="hidden" name="sotien" id="hiddenMomoAmount" />
    <input type="hidden" name="field_id" id="hiddenMomoFieldId" />
    <input type="hidden" name="booking_date" id="hiddenMomoBookingDate" />
    <input type="hidden" name="start_time" id="hiddenMomoStartTime" />
    <input type="hidden" name="duration" id="hiddenMomoDuration" />
    <input type="hidden" name="rent_ball" id="hiddenMomoRentBall" />
    <input type="hidden" name="rent_uniform" id="hiddenMomoRentUniform" />
    <input type="hidden" name="note" id="hiddenMomoNote" />
</form>

<!-- Hidden VNPay Payment Form -->
<form action="confirm_vnpay.php" method="post" id="hiddenVnpayForm" style="display: none;">
    <input type="hidden" name="sotien" id="hiddenVnpayAmount" />
    <input type="hidden" name="field_id" id="hiddenVnpayFieldId" />
    <input type="hidden" name="booking_date" id="hiddenVnpayBookingDate" />
    <input type="hidden" name="start_time" id="hiddenVnpayStartTime" />
    <input type="hidden" name="duration" id="hiddenVnpayDuration" />
    <input type="hidden" name="rent_ball" id="hiddenVnpayRentBall" />
    <input type="hidden" name="rent_uniform" id="hiddenVnpayRentUniform" />
    <input type="hidden" name="note" id="hiddenVnpayNote" />
    <input type="hidden" name="total_price" id="hiddenVnpayTotalPrice" />
</form>

<?php include 'footer.php'; ?>