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
    // Kiểm tra file ảnh
    if (!isset($_FILES['payment_image']) || $_FILES['payment_image']['error'] !== 0) {
        echo "<script>
            alert('Vui lòng upload ảnh bill thanh toán!');
            history.back();
        </script>";
        exit();
    }

    // Xử lý upload ảnh
    $image = $_FILES['payment_image'];
    $image_name = time() . '_' . $image['name'];
    $target_path = 'assets/bill/' . $image_name;

    // Kiểm tra và tạo thư mục nếu chưa tồn tại
    if (!file_exists('assets/bill')) {
        mkdir('assets/bill', 0777, true);
    }

    if (move_uploaded_file($image['tmp_name'], $target_path)) {
        // Tiếp tục xử lý insert booking với ảnh
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
                unlink($target_path);
            }
        }
    } else {
        echo "<script>
            alert('Có lỗi khi upload ảnh. Vui lòng thử lại!');
            history.back();
        </script>";
        exit();
    }
}
?>

<?php include 'header.php'; ?>
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
                                <label>Giờ bắt đầu</label>
                                <input type="time" name="start_time" class="form-control" 
                                       min="06:00" max="22:00" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Thời gian thuê (giờ)</label>
                                <select style="padding: 6px 12px;" name="duration" class="form-control" required>
                                    <option value="1">1 giờ</option>
                                    <option value="1.5">1.5 giờ</option>
                                    <option value="2">2 giờ</option>
                                    <option value="2.5">2.5 giờ</option>
                                    <option value="3">3 giờ</option>
                                </select>
                            </div>
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
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="bank" value="bank" required>
                                    <label class="form-check-label payment-label" for="bank">
                                        Chuyển khoản ngân hàng
                                    </label>
                                </div>
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
                            <h6>Quét mã QR để thanh toán MOMO</h6>
                            <div class="text-center">
                                <img src=""assets/momo-qr.jpg"" alt="MOMO QR";">
                            </div>
                        </div>

                        <div id="vnpayPayment" style="display: none;">
                            <h6>Quét mã QR để thanh toán VNPay</h6>
                            <div class="text-center">
                                <img src="assets/images/vnpay-qr.png" alt="VNPay QR" style="width: 200px;">
                            </div>
                        </div>

                        <div id="bankPayment" style="display: none;">
                            <h6>Thông tin chuyển khoản:</h6>
                            <div class="bank-details">
                                <p><strong>Ngân hàng:</strong> Vietcombank</p>
                                <p><strong>Số tài khoản:</strong> 1234567890</p>
                                <p><strong>Chủ tài khoản:</strong> NGUYEN VAN A</p>
                                <p><strong>Nội dung CK:</strong> <span id="transferContent"></span></p>
                            </div>
                        </div>

                        <!-- Phần upload ảnh bill -->
                        <div class="mt-4">
                            <h6>Upload ảnh bill thanh toán:</h6>
                            <div class="mb-3">
                                <input style="padding: 6px;" type="file" class="form-control" id="paymentImage" name="payment_image" 
                                       accept="image/*" required>
                                <div class="form-text">Vui lòng upload ảnh chụp màn hình bill thanh toán</div>
                            </div>
                            <div id="imagePreview" class="mt-2 text-center" style="display: none;">
                                <img src="" alt="Preview" style="max-width: 200px; max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="submit_booking" class="btn btn-primary">
                        Xác nhận thanh toán
                    </button>
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

.bank-details {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

.bank-details p {
    margin-bottom: 8px;
}

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
    const startTime = document.querySelector('input[name="start_time"]').value;
    const duration = parseFloat(document.querySelector('select[name="duration"]').value);
    
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
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật giá khi thay đổi thời gian hoặc dịch vụ
    const form = document.getElementById('bookingForm');
    const durationSelect = form.querySelector('[name="duration"]');
    const rentBallCheckbox = document.getElementById('rentBall');
    const rentUniformCheckbox = document.getElementById('rentUniform');
    const fieldPriceSpan = document.getElementById('fieldPrice');
    const totalPriceSpan = document.getElementById('totalPrice');
    const ballPriceRow = document.getElementById('ballPriceRow');
    const uniformPriceRow = document.getElementById('uniformPriceRow');
    
    function updatePrice() {
        const duration = parseFloat(durationSelect.value);
        const rentalPrice = <?php echo $field['rental_price']; ?>;
        const rentBall = rentBallCheckbox.checked;
        const rentUniform = rentUniformCheckbox.checked;
        
        // Tính tiền sân
        const fieldPrice = duration * rentalPrice;
        fieldPriceSpan.textContent = fieldPrice.toLocaleString('vi-VN') + ' đ';
        
        // Hiển thị/ẩn các dòng giá dịch vụ
        ballPriceRow.style.display = rentBall ? 'flex' : 'none';
        uniformPriceRow.style.display = rentUniform ? 'flex' : 'none';
        
        // Tính tổng tiền
        let totalPrice = fieldPrice;
        if (rentBall) totalPrice += 100000;
        if (rentUniform) totalPrice += 100000;
        
        totalPriceSpan.textContent = totalPrice.toLocaleString('vi-VN') + ' đ';
        
        // Cập nhật giá trong modal
        document.getElementById('modalTotalPrice').textContent = totalPrice.toLocaleString('vi-VN') + ' đ';
        document.getElementById('modalDepositAmount').textContent = (totalPrice * 0.5).toLocaleString('vi-VN') + ' đ';
    }
    
    // Gắn sự kiện cho các trường input
    durationSelect.addEventListener('change', updatePrice);
    rentBallCheckbox.addEventListener('change', updatePrice);
    rentUniformCheckbox.addEventListener('change', updatePrice);
    
    // Khởi tạo giá ban đầu
    updatePrice();
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
    document.getElementById('modal_start_time').value = mainForm.querySelector('[name="start_time"]').value;
    document.getElementById('modal_duration').value = mainForm.querySelector('[name="duration"]').value;
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

// Preview ảnh khi chọn file
document.getElementById('paymentImage').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        preview.style.display = 'block';
        preview.querySelector('img').src = e.target.result;
    }

    if (file) {
        reader.readAsDataURL(file);
    }
});

// Hiển thị phương thức thanh toán
function showPaymentMethod(method) {
    document.getElementById('momoPayment').style.display = 'none';
    document.getElementById('vnpayPayment').style.display = 'none';
    document.getElementById('bankPayment').style.display = 'none';
    
    document.getElementById(method + 'Payment').style.display = 'block';
}
</script>

<?php include 'footer.php'; ?>