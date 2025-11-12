<?php
include 'database/DBController.php';
session_start();

$user_id = @$_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit();
}

// Xử lý hủy đặt sân
if (isset($_GET['cancel']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    
    // Kiểm tra xem booking có phải của user hiện tại không
    $check_booking = mysqli_query($conn, "SELECT * FROM bookings WHERE id = '$booking_id' AND user_id = '$user_id'") or die('Query failed');
    
    if (mysqli_num_rows($check_booking) > 0) {
        $booking = mysqli_fetch_assoc($check_booking);
        $field_id = $booking['field_id'];
        
        // Chỉ cho phép hủy các đơn chờ xác nhận
        if ($booking['status'] == 'Chờ xác nhận') {
            mysqli_query($conn, "UPDATE bookings SET status = 'Đã hủy' WHERE id = '$booking_id'") or die('Query failed');
            $message[] = 'Hủy đặt sân thành công!';
        } else {
            $message[] = 'Không thể hủy đặt sân ở trạng thái này!';
        }
    }
}

// Xử lý hủy đặt sân định kỳ
if (isset($_GET['cancel_recurring']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    
    // Kiểm tra xem booking có phải của user hiện tại không
    $check_booking = mysqli_query($conn, "SELECT * FROM recurring_bookings WHERE id = '$booking_id' AND user_id = '$user_id'") or die('Query failed');
    
    if (mysqli_num_rows($check_booking) > 0) {
        $booking = mysqli_fetch_assoc($check_booking);
        
        // Chỉ cho phép hủy các đơn chờ xác nhận
        if ($booking['status'] == 'Chờ xác nhận') {
            mysqli_query($conn, "UPDATE recurring_bookings SET status = 'Đã hủy' WHERE id = '$booking_id'") or die('Query failed');
            $message[] = 'Hủy đặt sân định kỳ thành công!';
        } else {
            $message[] = 'Không thể hủy đặt sân ở trạng thái này!';
        }
    }
}

// Lấy danh sách đặt sân thường
$bookings_query = mysqli_query($conn, "
    SELECT b.*, f.name as field_name, f.image as field_image, f.address, f.field_type 
    FROM bookings b 
    JOIN football_fields f ON b.field_id = f.id 
    WHERE b.user_id = '$user_id' 
    ORDER BY b.booking_date DESC, b.start_time DESC
") or die('Query failed');

// Lấy danh sách đặt sân định kỳ
$recurring_bookings_query = mysqli_query($conn, "
    SELECT rb.*, f.name as field_name, f.image as field_image, f.address, f.field_type, f.rental_price
    FROM recurring_bookings rb
    JOIN football_fields f ON rb.field_id = f.id 
    WHERE rb.user_id = '$user_id' 
    ORDER BY rb.start_date DESC
") or die('Query failed');

// Thêm vào đầu file
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $duration = floatval($_POST['duration']);
    $rent_ball = isset($_POST['rent_ball']) ? 1 : 0;
    $rent_uniform = isset($_POST['rent_uniform']) ? 1 : 0;
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    // Tính thời gian kết thúc
    $start_timestamp = strtotime("$booking_date $start_time");
    $duration_seconds = $duration * 3600;
    $end_timestamp = $start_timestamp + $duration_seconds;
    $end_time = date('H:i', $end_timestamp);

    // Lấy thông tin field_id và giá sân
    $booking_query = mysqli_query($conn, "SELECT b.field_id, f.rental_price 
        FROM bookings b 
        JOIN football_fields f ON b.field_id = f.id 
        WHERE b.id = '$booking_id'") or die('Query failed');
    $booking_info = mysqli_fetch_assoc($booking_query);
    
    // Tính giá mới
    $field_price = $booking_info['rental_price'] * $duration;
    $total_price = $field_price;
    if ($rent_ball) $total_price += 100000;
    if ($rent_uniform) $total_price += 100000;

    // Kiểm tra trùng lịch
    $check_booking = mysqli_query($conn, "SELECT * FROM bookings 
        WHERE field_id = '{$booking_info['field_id']}' 
        AND booking_date = '$booking_date'
        AND id != '$booking_id'
        AND ((start_time <= '$start_time' AND end_time > '$start_time')
        OR (start_time < '$end_time' AND end_time >= '$end_time')
        OR (start_time >= '$start_time' AND end_time <= '$end_time'))
        AND status != 'Đã hủy'") or die('Query failed');

    if (mysqli_num_rows($check_booking) > 0) {
        echo "<script>
            alert('Sân đã được đặt trong khoảng thời gian này!');
            window.location.href = 'my-bookings.php';
        </script>";
        exit();
    } else {
        $update_query = mysqli_query($conn, "UPDATE bookings SET 
            booking_date = '$booking_date',
            start_time = '$start_time',
            end_time = '$end_time',
            duration = '$duration',
            field_price = '$field_price',
            rent_ball = '$rent_ball',
            rent_uniform = '$rent_uniform',
            total_price = '$total_price',
            note = '$note'
            WHERE id = '$booking_id'") or die('Query failed');

        if ($update_query) {
            echo "<script>
                alert('Cập nhật đơn đặt sân thành công!');
                window.location.href = 'my-bookings.php';
            </script>";
            exit();
        } else {
            echo "<script>
                alert('Cập nhật đơn đặt sân thất bại!');
                window.location.href = 'my-bookings.php';
            </script>";
            exit();
        }
    }
}

// Lấy thông tin tài khoản của user
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);
?>

<?php include 'header.php'; ?>
<!-- Trong header.php -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- // Xử lý hủy đặt sân -->

<div class="my-bookings-container py-5">
    <div class="container">
        <h2 class="page-title">Lịch Sử Đặt Sân</h2>
        
        <?php if(!$user_data['bank_account_number']): ?>
            <div class="alert alert-warning">
                <strong>Lưu ý:</strong> Vui lòng cập nhật thông tin tài khoản ngân hàng trong trang cá nhân để được hoàn tiền khi hủy đặt sân.
                <a style="text-decoration: none;" href="./personal.php" class="alert-link">Cập nhật ngay</a>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="bookingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="normal-tab" data-bs-toggle="tab" data-bs-target="#normal" type="button" role="tab">
                    Đặt sân thường
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recurring-tab" data-bs-toggle="tab" data-bs-target="#recurring" type="button" role="tab">
                    Đặt sân định kỳ
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="bookingTabsContent">
            <!-- Đặt sân thường -->
            <div class="tab-pane fade show active" id="normal" role="tabpanel">
                <?php if(mysqli_num_rows($bookings_query) > 0): ?>
                    <div class="row">
                        <?php while($booking = mysqli_fetch_assoc($bookings_query)): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="booking-card">
                                    <div class="booking-header">
                                        <div class="field-info">
                                            <img src="assets/fields/<?php echo $booking['field_image']; ?>" alt="<?php echo $booking['field_name']; ?>">
                                            <div>
                                                <h4><?php echo $booking['field_name']; ?></h4>
                                                <p><i class="fas fa-futbol"></i> Sân <?php echo $booking['field_type']; ?> người</p>
                                                <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p>
                                                <p><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="booking-actions-top">
                                            <div class="booking-status <?php 
                                                switch($booking['status']) {
                                                    case 'Đã hủy':
                                                        echo 'cancelled';
                                                        break;
                                                    case 'Chờ xác nhận':
                                                        echo 'pending';
                                                        break;
                                                    case 'Đã xác nhận':
                                                        echo 'confirmed';
                                                        break;
                                                    default:
                                                        echo 'default';
                                                }
                                            ?>">
                                                <?php echo $booking['status']; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Xem Chi Tiết -->
                                <div class="modal fade" id="detailModal<?php echo $booking['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Chi tiết đơn đặt sân #<?php echo $booking['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="booking-details">
                                                    <!-- Thông tin sân -->
                                                    <div class="field-info mb-4">
                                                        <img src="assets/fields/<?php echo $booking['field_image']; ?>" alt="<?php echo $booking['field_name']; ?>">
                                                        <div>
                                                            <h4><?php echo $booking['field_name']; ?></h4>
                                                            <p><i class="fas fa-futbol"></i> Sân <?php echo $booking['field_type']; ?> người</p>
                                                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $booking['address']; ?></p>
                                                        </div>
                                                    </div>

                                                    <!-- Thông tin đặt sân -->
                                                    <div class="detail-item">
                                                        <i class="fas fa-calendar"></i>
                                                        <span>Ngày đặt: <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-clock"></i>
                                                        <span>Thời gian: <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-hourglass-half"></i>
                                                        <span>Thời lượng: <?php echo $booking['duration'] == floor($booking['duration']) ? floor($booking['duration']) : number_format($booking['duration'], 1); ?> giờ</span>
                                                    </div>

                                                    <!-- Thông tin giá -->
                                                    <div class="services-price">
                                                        <div class="price-item">
                                                            <span>Tiền sân:</span>
                                                            <span><?php echo number_format($booking['field_price'], 0, ',', '.'); ?> đ</span>
                                                        </div>
                                                        <?php if($booking['rent_ball']): ?>
                                                        <div class="price-item">
                                                            <span>Thuê bóng:</span>
                                                            <span>100.000 đ</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if($booking['rent_uniform']): ?>
                                                        <div class="price-item">
                                                            <span>Thuê áo:</span>
                                                            <span>100.000 đ</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="price-item">
                                                            <span>Đã đặt cọc:</span>
                                                            <span><?php echo number_format($booking['total_price'] * 0.5, 0, ',', '.'); ?> đ</span>
                                                        </div>
                                                        <div class="price-item total">
                                                            <span>Tổng tiền:</span>
                                                            <span><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</span>
                                                        </div>
                                                    </div>

                                                    <!-- Thông tin hủy đơn -->
                                                    <?php if($booking['status'] == 'Đã hủy'): ?>
                                                        <div class="cancel-info">
                                                            <h6 class="cancel-title">Thông tin hủy đơn</h6>
                                                            <?php if($booking['cancel_reason']): ?>
                                                                <div class="cancel-reason">
                                                                    <i class="fas fa-info-circle"></i>
                                                                    <span>Lý do hủy: <?php echo $booking['cancel_reason']; ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if($booking['refund_image']): ?>
                                                                <div class="refund-image">
                                                                    <h6>Ảnh bill hoàn cọc:</h6>
                                                                    <img src="assets/refund/<?php echo $booking['refund_image']; ?>" 
                                                                         alt="Bill hoàn cọc" 
                                                                         onclick="window.open(this.src)">
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if($booking['cancel_date']): ?>
                                                                <div class="cancel-date">
                                                                    <i class="fas fa-calendar-times"></i>
                                                                    <span>Ngày hủy: <?php echo date('d/m/Y H:i', strtotime($booking['cancel_date'])); ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Ghi chú -->
                                                    <?php if($booking['note']): ?>
                                                        <div class="booking-note">
                                                            <i class="fas fa-sticky-note"></i>
                                                            <span>Ghi chú: <?php echo $booking['note']; ?></span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Các nút thao tác -->
                                                    <div class="booking-actions">
                                                        <?php if($booking['status'] == 'Chờ xác nhận'): ?>
                                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $booking['id']; ?>">
                                                                <i class="fas fa-edit"></i> Chỉnh sửa
                                                            </button>
                                                            <a href="?cancel=1&booking_id=<?php echo $booking['id']; ?>" 
                                                               class="btn btn-danger"
                                                               onclick="return confirm('Bạn có chắc chắn muốn hủy đặt sân này?')">
                                                                <i class="fas fa-times"></i> Hủy đặt sân
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="field-detail.php?id=<?php echo $booking['field_id']; ?>" 
                                                           class="btn btn-info">
                                                            <i class="fas fa-info-circle"></i> Xem sân
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times"></i>
                        <p>Bạn chưa có lịch đặt sân nào</p>
                        <a href="index.php" class="btn btn-primary">Đặt sân ngay</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Đặt sân định kỳ -->
            <div class="tab-pane fade" id="recurring" role="tabpanel">
                <?php if(mysqli_num_rows($recurring_bookings_query) > 0): ?>
                    <div class="row">
                        <?php while($booking = mysqli_fetch_assoc($recurring_bookings_query)): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="booking-card">
                                    <div class="booking-header">
                                        <div class="field-info">
                                            <img src="assets/fields/<?php echo $booking['field_image']; ?>" alt="<?php echo $booking['field_name']; ?>">
                                            <div>
                                                <h4><?php echo $booking['field_name']; ?></h4>
                                                <p><i class="fas fa-futbol"></i> Sân <?php echo $booking['field_type']; ?> người</p>
                                                <p><i class="fas fa-calendar"></i> 
                                                    <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                                </p>
                                                <p><i class="fas fa-clock"></i> 
                                                    <?php echo date('H:i', strtotime($booking['start_time'])); ?> 
                                                    (<?php echo $booking['duration']; ?> giờ)
                                                </p>
                                                <p><i class="fas fa-calendar-day"></i> 
                                                    Hàng <?php echo convertDayToVietnamese($booking['day_of_week']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="booking-actions-top">
                                            <div class="booking-status <?php 
                                                switch($booking['status']) {
                                                    case 'Đã hủy':
                                                        echo 'cancelled';
                                                        break;
                                                    case 'Chờ xác nhận':
                                                        echo 'pending';
                                                        break;
                                                    case 'Đã xác nhận':
                                                        echo 'confirmed';
                                                        break;
                                                    default:
                                                        echo 'default';
                                                }
                                            ?>">
                                                <?php echo $booking['status']; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#recurringDetailModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Chi tiết đặt sân định kỳ -->
                                <div class="modal fade" id="recurringDetailModal<?php echo $booking['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Chi tiết đặt sân định kỳ #<?php echo $booking['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="booking-details">
                                                    <!-- Thông tin sân -->
                                                    <div class="field-info mb-4">
                                                        <img src="assets/fields/<?php echo $booking['field_image']; ?>" 
                                                             alt="<?php echo $booking['field_name']; ?>">
                                                        <div>
                                                            <h4><?php echo $booking['field_name']; ?></h4>
                                                            <p><i class="fas fa-futbol"></i> Sân <?php echo $booking['field_type']; ?> người</p>
                                                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $booking['address']; ?></p>
                                                        </div>
                                                    </div>

                                                    <!-- Chi tiết lịch đặt -->
                                                    <div class="recurring-details">
                                                        <h6>Thông tin đặt sân định kỳ:</h6>
                                                        <p><i class="fas fa-calendar-alt"></i> Từ ngày: <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?></p>
                                                        <p><i class="fas fa-calendar-alt"></i> Đến ngày: <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?></p>
                                                        <p><i class="fas fa-clock"></i> Giờ bắt đầu: <?php echo date('H:i', strtotime($booking['start_time'])); ?></p>
                                                        <p><i class="fas fa-hourglass-half"></i> Thời lượng: <?php echo $booking['duration']; ?> giờ</p>
                                                        <p><i class="fas fa-calendar-day"></i> Lặp lại: Hàng <?php echo convertDayToVietnamese($booking['day_of_week']); ?></p>
                                                    </div>

                                                    <!-- Dịch vụ thêm -->
                                                    <div class="additional-services">
                                                        <h6>Dịch vụ thêm:</h6>
                                                        <?php if($booking['rent_ball'] || $booking['rent_uniform']): ?>
                                                            <?php if($booking['rent_ball']): ?>
                                                                <p><i class="fas fa-futbol"></i> Thuê bóng</p>
                                                            <?php endif; ?>
                                                            <?php if($booking['rent_uniform']): ?>
                                                                <p><i class="fas fa-tshirt"></i> Thuê áo</p>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <p>Không có dịch vụ thêm</p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Thông tin thanh toán -->
                                                    <div class="price-summary">
                                                        <h6>Thông tin thanh toán:</h6>
                                                        <div class="price-details">
                                                            <div class="price-item total">
                                                                <span>Tổng tiền:</span>
                                                                <span><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</span>
                                                            </div>
                                                            <div class="price-item deposit">
                                                                <span>Đã đặt cọc (50%):</span>
                                                                <span><?php echo number_format($booking['total_price'] * 0.5, 0, ',', '.'); ?> đ</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Ghi chú -->
                                                    <?php if($booking['note']): ?>
                                                        <div class="booking-note">
                                                            <i class="fas fa-sticky-note"></i>
                                                            <span>Ghi chú: <?php echo $booking['note']; ?></span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Ảnh bill thanh toán -->
                                                    <?php if($booking['payment_image']): ?>
                                                        <div class="payment-image">
                                                            <h6>Ảnh bill thanh toán:</h6>
                                                            <img src="assets/bill/<?php echo $booking['payment_image']; ?>" 
                                                                 alt="Bill thanh toán" 
                                                                 onclick="window.open(this.src)">
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Các nút thao tác -->
                                                    <div class="booking-actions">
                                                        <?php if($booking['status'] == 'Chờ xác nhận'): ?>
                                                            <a href="?cancel_recurring=1&booking_id=<?php echo $booking['id']; ?>" 
                                                               class="btn btn-danger"
                                                               onclick="return confirm('Bạn có chắc chắn muốn hủy đặt sân định kỳ này?')">
                                                                <i class="fas fa-times"></i> Hủy đặt sân
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="field-detail.php?id=<?php echo $booking['field_id']; ?>" 
                                                           class="btn btn-info">
                                                            <i class="fas fa-info-circle"></i> Xem sân
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times"></i>
                        <p>Bạn chưa có lịch đặt sân định kỳ nào</p>
                        <a href="index.php" class="btn btn-primary">Đặt sân ngay</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sửa lại phần script -->
<script>
function initializeEditForm(bookingId, rentalPrice) {
    const form = document.getElementById('editForm' + bookingId);
    if (!form) return; // Kiểm tra form tồn tại

    const durationSelect = form.querySelector('[name="duration"]');
    const rentBallCheckbox = document.getElementById('rentBall' + bookingId);
    const rentUniformCheckbox = document.getElementById('rentUniform' + bookingId);
    const fieldPriceSpan = document.getElementById('fieldPrice' + bookingId);
    const totalPriceSpan = document.getElementById('totalPrice' + bookingId);
    const ballPriceRow = document.getElementById('ballPriceRow' + bookingId);
    const uniformPriceRow = document.getElementById('uniformPriceRow' + bookingId);

    function updatePrice() {
        const duration = parseFloat(durationSelect.value);
        const fieldPrice = rentalPrice * duration;
        let total = fieldPrice;

        fieldPriceSpan.textContent = fieldPrice.toLocaleString('vi-VN') + ' đ';

        if (rentBallCheckbox.checked) {
            total += 100000;
            ballPriceRow.style.display = 'flex';
        } else {
            ballPriceRow.style.display = 'none';
        }

        if (rentUniformCheckbox.checked) {
            total += 100000;
            uniformPriceRow.style.display = 'flex';
        } else {
            uniformPriceRow.style.display = 'none';
        }

        totalPriceSpan.textContent = total.toLocaleString('vi-VN') + ' đ';
    }

    durationSelect.addEventListener('change', updatePrice);
    rentBallCheckbox.addEventListener('change', updatePrice);
    rentUniformCheckbox.addEventListener('change', updatePrice);

    form.addEventListener('submit', function(e) {
        const timeInput = form.querySelector('[name="start_time"]');
        const time = timeInput.value;
        const [hours, minutes] = time.split(':').map(Number);
        
        if (hours < 6 || (hours === 22 && minutes > 0) || hours > 22) {
            e.preventDefault();
            alert('Vui lòng chọn giờ đặt sân từ 6:00 đến 22:00');
        }
    });
}

// Sửa lại cách khởi tạo form
<?php 
// Reset con trỏ của bookings_query
mysqli_data_seek($bookings_query, 0);
while($booking = mysqli_fetch_assoc($bookings_query)): 
    // Lấy rental_price từ football_fields
    $field_query = mysqli_query($conn, "SELECT rental_price FROM football_fields WHERE id = '{$booking['field_id']}'");
    $field = mysqli_fetch_assoc($field_query);
?>
    initializeEditForm(<?php echo $booking['id']; ?>, <?php echo $field['rental_price']; ?>);
<?php endwhile; ?>
</script>

<style>
.my-bookings-container {
    background: #f8f9fa;
    min-height: 100vh;
}

.page-title {
    color: #1B4D3E;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #28a745;
    display: inline-block;
}

.booking-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.booking-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.field-info {
    display: flex;
    gap: 15px;
}

.field-info img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.field-info h4 {
    color: #1B4D3E;
    margin-bottom: 8px;
    font-size: 16px;
}

.field-info p {
    margin-bottom: 4px;
    color: #666;
    font-size: 14px;
}

.field-info i {
    width: 16px;
    color: #28a745;
    margin-right: 5px;
}

.booking-actions-top {
    text-align: right;
}

.booking-status {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.booking-status.cancelled {
    background-color: #dc3545;
    color: white;
}

.booking-status.pending {
    background-color: #ffc107;
    color: #000;
}

.booking-status.confirmed {
    background-color: #28a745;
    color: white;
}

.booking-status.default {
    background-color: #6c757d;
    color: white;
}

.btn-outline-primary {
    border-color: #1B4D3E;
    color: #1B4D3E;
}

.btn-outline-primary:hover {
    background-color: #1B4D3E;
    color: white;
}

/* Modal styles */
.modal-body .field-info {
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.modal-body .field-info img {
    width: 100px;
    height: 100px;
}

.booking-details {
    padding: 20px;
}

.detail-item {
    margin-bottom: 10px;
    color: #666;
}

.detail-item i {
    width: 25px;
    color: #28a745;
}

.services-price {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin: 15px 0;
}

.price-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding-bottom: 8px;
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
    font-size: 16px;
    border-top: 2px solid #ddd;
    margin-top: 8px;
    padding-top: 8px;
}

.booking-note {
    background: #fff3cd;
    padding: 10px 15px;
    border-radius: 8px;
    margin: 15px 0;
    color: #856404;
}

.booking-note i {
    margin-right: 10px;
}

.booking-actions {
    display: flex;
    gap: 10px;
}

.booking-actions .btn {
    flex: 1;
}

.btn-secondary.disabled {
    background: #6c757d;
    opacity: 0.8;
    cursor: not-allowed;
}

.no-bookings {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.no-bookings i {
    font-size: 50px;
    color: #dc3545;
    margin-bottom: 20px;
}

.no-bookings p {
    color: #666;
    margin-bottom: 20px;
}

.cancel-info {
    background: #f8d7da;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.cancel-title {
    color: #721c24;
    margin-bottom: 10px;
    font-size: 16px;
    font-weight: 600;
}

.cancel-reason {
    margin-bottom: 10px;
    color: #721c24;
}

.cancel-reason i {
    margin-right: 8px;
}

.refund-image {
    margin: 10px 0;
}

.refund-image h6 {
    color: #721c24;
    margin-bottom: 8px;
    font-size: 14px;
}

.refund-image img {
    max-width: 200px;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s;
}

.refund-image img:hover {
    transform: scale(1.05);
}

.cancel-date {
    color: #721c24;
    font-size: 14px;
    margin-top: 10px;
}

.cancel-date i {
    margin-right: 8px;
}

/* Thêm vào phần style hiện có */
.nav-tabs {
    border-bottom: 2px solid #28a745;
}

.nav-tabs .nav-link {
    color: #28a745 !important;
    border: none;
    padding: 10px 20px;
    margin-right: 5px;
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-link.active {
    color: white !important;
    background-color: #28a745;
}

.recurring-details {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.recurring-details h6 {
    color: #28a745;
    margin-bottom: 10px;
}

.recurring-details p {
    margin-bottom: 8px;
}

.recurring-details i {
    width: 25px;
    color: #28a745;
}

.additional-services {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.additional-services h6 {
    color: #28a745;
    margin-bottom: 10px;
}

.additional-services p {
    margin-bottom: 5px;
}

.additional-services i {
    width: 25px;
    color: #28a745;
}

.payment-image {
    margin: 15px 0;
}

.payment-image h6 {
    color: #28a745;
    margin-bottom: 10px;
}

.payment-image img {
    max-width: 200px;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s;
}

.payment-image img:hover {
    transform: scale(1.05);
}
</style>

<?php include 'footer.php'; ?> 

<?php
// Hàm chuyển đổi thứ sang tiếng Việt
function convertDayToVietnamese($day) {
    $dayMap = [
        'Monday' => 'Thứ 2',
        'Tuesday' => 'Thứ 3',
        'Wednesday' => 'Thứ 4',
        'Thursday' => 'Thứ 5',
        'Friday' => 'Thứ 6',
        'Saturday' => 'Thứ 7',
        'Sunday' => 'Chủ nhật'
    ];
    return $dayMap[$day];
}
?> 

