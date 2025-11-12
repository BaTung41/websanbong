<?php
include '../database/DBController.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// Xử lý cập nhật trạng thái đơn đặt sân
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    $field_id = $_POST['field_id'];

    if ($new_status !== "") {
        // Nếu hủy đơn, cập nhật trạng thái sân thành "Đang trống"
        if ($new_status == 'Đã hủy') {
            mysqli_query($conn, "UPDATE football_fields SET status = 'Đang trống' WHERE id = '$field_id'");
        }
        // Nếu xác nhận đơn, cập nhật trạng thái sân thành "Đã đặt"
        else if ($new_status == 'Đã xác nhận') {
            mysqli_query($conn, "UPDATE football_fields SET status = 'Đã đặt' WHERE id = '$field_id'");
        }

        $update_query = mysqli_query($conn, "UPDATE bookings SET status = '$new_status' WHERE id = '$booking_id'");

        if ($update_query) {
            $message[] = 'Cập nhật trạng thái đơn đặt sân thành công!';
        } else {
            $message[] = 'Cập nhật trạng thái đơn đặt sân thất bại!';
        }
    }
}

// Xử lý hủy đơn
if(isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $field_id = $_POST['field_id'];
    $cancel_reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']);
    
    // Xử lý upload ảnh bill hoàn cọc
    if(isset($_FILES['refund_image']) && $_FILES['refund_image']['error'] === 0) {
        $image = $_FILES['refund_image'];
        $image_name = time() . '_refund_' . $image['name'];
        $target_path = '../assets/refund/' . $image_name;

        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists('../assets/refund')) {
            mkdir('../assets/refund', 0777, true);
        }

        if(move_uploaded_file($image['tmp_name'], $target_path)) {
            // Cập nhật trạng thái đơn
            $update_query = "UPDATE bookings SET 
                status = 'Đã hủy',
                cancel_reason = '$cancel_reason',
                refund_image = '$image_name',
                cancel_date = NOW()
            WHERE id = '$booking_id'";

            if(mysqli_query($conn, $update_query)) {
                // Cập nhật trạng thái sân
                mysqli_query($conn, "UPDATE football_fields SET status = 'Đang trống' WHERE id = '$field_id'");
                $message[] = 'Đã hủy đơn và lưu thông tin hoàn cọc!';
            } else {
                $message[] = 'Có lỗi xảy ra khi hủy đơn!';
                // Xóa ảnh nếu cập nhật database thất bại
                unlink($target_path);
            }
        } else {
            $message[] = 'Lỗi upload ảnh hoàn tiền!';
        }
    } else {
        $message[] = 'Vui lòng upload ảnh bill hoàn cọc!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn đặt sân</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .nav-tabs {
            border-bottom: 2px solid #28a745;
        }

        .nav-tabs .nav-link {
            color: #fff !important;
            border: none;
            padding: 10px 20px;
            margin-right: 5px;
            border-radius: 8px 8px 0 0;
        }

        .nav-tabs .nav-link.active {
            color: white !important;
            background-color: #28a745 !important;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
            <?php
            if (isset($message)) {
                foreach ($message as $msg) {
                    echo '
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <span>' . $msg . '</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                }
            }
            ?>
            <div style="background-color: #28a745" class="text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Quản lý Đơn đặt sân</h1>
            </div>

            <div class="container">
                <ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
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

                <div class="tab-content" id="orderTabsContent">
                    <!-- Tab đặt sân thường -->
                    <div class="tab-pane fade show active" id="normal" role="tabpanel">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên sân</th>
                                    <th>Khách hàng</th>
                                    <th>Ngày đặt</th>
                                    <th>Thời gian</th>
                                    <th>Dịch vụ thêm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $select_bookings = mysqli_query($conn, "
                                    SELECT b.*, f.name as field_name, f.rental_price, u.username as user_name, u.email, u.phone 
                                    FROM bookings b 
                                    JOIN football_fields f ON b.field_id = f.id 
                                    JOIN users u ON b.user_id = u.user_id 
                                    ORDER BY b.booking_date DESC, b.start_time DESC
                                ") or die('Query failed');

                                if (mysqli_num_rows($select_bookings) > 0) {
                                    while ($booking = mysqli_fetch_assoc($select_bookings)) {
                                ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo $booking['field_name']; ?></td>
                                    <td>
                                        <?php echo $booking['user_name']; ?><br>
                                        <small><?php echo $booking['phone']; ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <?php 
                                        echo $booking['start_time'] . ' - ' . $booking['end_time']; 
                                        echo '<br><small>(' . $booking['duration'] . ' giờ)</small>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $services = [];
                                        if ($booking['rent_ball']) $services[] = 'Thuê bóng';
                                        if ($booking['rent_uniform']) $services[] = 'Thuê áo';
                                        echo $services ? implode(', ', $services) : 'Không có';
                                        ?>
                                    </td>
                                    <td>
                                        <span>Tổng: <?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</span>
                                        <br>
                                        <span>Đã cọc: <?php echo number_format($booking['total_price'] * 0.5, 0, ',', '.'); ?> đ</span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] == 'Chờ xác nhận'): ?>
                                            <form action="" method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="field_id" value="<?php echo $booking['field_id']; ?>">
                                                <input type="hidden" name="status" value="Đã xác nhận">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Xác nhận
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelModal_<?php echo $booking['id']; ?>">
                                                    <i class="fas fa-times"></i> Hủy đơn
                                                </button>
                                            </form>

                                            <!-- Modal Hủy đơn -->
                                            <div class="modal fade" id="cancelModal_<?php echo $booking['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Hủy đơn đặt sân #<?php echo $booking['id']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="" method="POST" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                                <input type="hidden" name="field_id" value="<?php echo $booking['field_id']; ?>">
                                                                
                                                                <!-- Thông tin đơn hàng -->
                                                                <div class="booking-info mb-3">
                                                                    <h6>Thông tin đơn hàng:</h6>
                                                                    <p><strong>Tổng tiền:</strong> <?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</p>
                                                                    <p><strong>Tiền cọc đã nhận:</strong> <?php echo number_format($booking['total_price'] * 0.5, 0, ',', '.'); ?> đ</p>
                                                                </div>

                                                                <!-- Thông tin tài khoản user -->
                                                                <?php
                                                                $user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '{$booking['user_id']}'");
                                                                $user_data = mysqli_fetch_assoc($user_query);
                                                                ?>
                                                                <div class="bank-info mb-3">
                                                                    <h6>Thông tin hoàn tiền:</h6>
                                                                    <?php if(!empty($user_data['bank_account_number'])): ?>
                                                                        <p><strong>Ngân hàng:</strong> <?php echo $user_data['bank_name']; ?></p>
                                                                        <p><strong>Số tài khoản:</strong> <?php echo $user_data['bank_account_number']; ?></p>
                                                                        <p><strong>Chủ tài khoản:</strong> <?php echo $user_data['bank_account_name']; ?></p>
                                                                    <?php else: ?>
                                                                        <div class="alert alert-warning">
                                                                            Người dùng chưa cập nhật thông tin tài khoản ngân hàng!
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Lý do hủy <span class="text-danger">*</span></label>
                                                                    <textarea name="cancel_reason" class="form-control" required></textarea>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Ảnh bill hoàn cọc <span class="text-danger">*</span></label>
                                                                    <input type="file" class="form-control" name="refund_image" accept="image/*" required>
                                                                    <div class="form-text">Upload ảnh chụp màn hình chuyển khoản hoàn tiền</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                <button type="submit" name="cancel_booking" class="btn btn-danger">
                                                                    Xác nhận hủy đơn
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($booking['status'] == 'Đã xác nhận'): ?>
                                            <span class="badge bg-success">Đã xác nhận</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Đã hủy</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#bookingModal<?php echo $booking['id']; ?>">
                                            Chi tiết
                                        </button>

                                        <!-- Modal Chi tiết -->
                                        <div class="modal fade" id="bookingModal<?php echo $booking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            Chi tiết đơn đặt sân #<?php echo $booking['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <h6>Thông tin khách hàng</h6>
                                                                <p><strong>Tên:</strong> <?php echo $booking['user_name']; ?></p>
                                                                <p><strong>Email:</strong> <?php echo $booking['email']; ?></p>
                                                                <p><strong>SĐT:</strong> <?php echo $booking['phone']; ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Thông tin đặt sân</h6>
                                                                <p><strong>Sân:</strong> <?php echo $booking['field_name']; ?></p>
                                                                <p><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p>
                                                                <p><strong>Thời gian:</strong> <?php echo $booking['start_time'] . ' - ' . $booking['end_time']; ?></p>
                                                            </div>
                                                        </div>

                                                        <div class="price-details">
                                                            <h6>Chi tiết giá</h6>
                                                            <table class="table table-bordered">
                                                                <tr>
                                                                    <td>Tiền sân (<?php echo $booking['duration']; ?> giờ)</td>
                                                                    <td><?php echo number_format($booking['field_price'], 0, ',', '.'); ?> đ</td>
                                                                </tr>
                                                                <?php if ($booking['rent_ball']): ?>
                                                                <tr>
                                                                    <td>Thuê bóng</td>
                                                                    <td>100.000 đ</td>
                                                                </tr>
                                                                <?php endif; ?>
                                                                <?php if ($booking['rent_uniform']): ?>
                                                                <tr>
                                                                    <td>Thuê áo</td>
                                                                    <td>100.000 đ</td>
                                                                </tr>
                                                                <?php endif; ?>
                                                                <tr class="table-success">
                                                                    <th>Tổng cộng</th>
                                                                    <th><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</th>
                                                                    <th>Đã cọc: <?php echo number_format($booking['total_price'] * 0.5, 0, ',', '.'); ?> đ</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>Ảnh bill thanh toán cọc</td>
                                                                    <td><img src="../assets/bill/<?php echo $booking['payment_image']; ?>" alt="<?php echo $booking['user_name']; ?>" style="width: 200px; height: 300px;"></td>
                                                                </tr>
                                                            </table>
                                                        </div>

                                                        <?php if ($booking['note']): ?>
                                                        <div class="booking-note">
                                                            <h6>Ghi chú</h6>
                                                            <p class="text-muted"><?php echo $booking['note']; ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="9" class="text-center">Chưa có đơn đặt sân nào.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab đặt sân định kỳ -->
                    <div class="tab-pane fade" id="recurring" role="tabpanel">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên sân</th>
                                    <th>Khách hàng</th>
                                    <th>Thời gian</th>
                                    <th>Lặp lại</th>
                                    <th>Dịch vụ thêm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $select_recurring = mysqli_query($conn, "
                                    SELECT rb.*, f.name as field_name, f.rental_price, u.username as user_name, u.email, u.phone 
                                    FROM recurring_bookings rb 
                                    JOIN football_fields f ON rb.field_id = f.id 
                                    JOIN users u ON rb.user_id = u.user_id 
                                    ORDER BY rb.start_date DESC
                                ") or die('Query failed');

                                if (mysqli_num_rows($select_recurring) > 0) {
                                    while ($booking = mysqli_fetch_assoc($select_recurring)) {
                                ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo $booking['field_name']; ?></td>
                                    <td>
                                        <?php echo $booking['user_name']; ?><br>
                                        <small><?php echo $booking['phone']; ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        echo date('d/m/Y', strtotime($booking['start_date'])) . ' - ' . 
                                             date('d/m/Y', strtotime($booking['end_date'])); 
                                        echo '<br><small>' . date('H:i', strtotime($booking['start_time'])) . 
                                             ' (' . $booking['duration'] . ' giờ)</small>';
                                        ?>
                                    </td>
                                    <td>Hàng <?php echo convertDayToVietnamese($booking['day_of_week']); ?></td>
                                    <td>
                                        <?php
                                        $services = [];
                                        if ($booking['rent_ball']) $services[] = 'Thuê bóng';
                                        if ($booking['rent_uniform']) $services[] = 'Thuê áo';
                                        echo $services ? implode(', ', $services) : 'Không có';
                                        ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</td>
                                    <td>
                                        <?php if ($booking['status'] == 'Chờ xác nhận'): ?>
                                            <form action="" method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="recurring_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="field_id" value="<?php echo $booking['field_id']; ?>">
                                                <button type="submit" name="confirm_recurring" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Xác nhận
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelRecurringModal_<?php echo $booking['id']; ?>">
                                                    <i class="fas fa-times"></i> Hủy đơn
                                                </button>
                                            </form>

                                            <!-- Modal Hủy đơn định kỳ -->
                                            <div class="modal fade" id="cancelRecurringModal_<?php echo $booking['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Hủy đơn đặt sân định kỳ #<?php echo $booking['id']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="" method="POST" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="recurring_id" value="<?php echo $booking['id']; ?>">
                                                                <input type="hidden" name="field_id" value="<?php echo $booking['field_id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Lý do hủy <span class="text-danger">*</span></label>
                                                                    <textarea name="cancel_reason" class="form-control" required></textarea>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Ảnh bill hoàn cọc <span class="text-danger">*</span></label>
                                                                    <input type="file" class="form-control" name="refund_image" accept="image/*" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                <button type="submit" name="cancel_recurring" class="btn btn-danger">
                                                                    Xác nhận hủy đơn
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $booking['status'] == 'Đã xác nhận' ? 'success' : 'danger'; ?>">
                                                <?php echo $booking['status']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#recurringModal<?php echo $booking['id']; ?>">
                                            Chi tiết
                                        </button>

                                        <!-- Modal Chi tiết đặt sân định kỳ -->
                                        <div class="modal fade" id="recurringModal<?php echo $booking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            Chi tiết đơn đặt sân định kỳ #<?php echo $booking['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <h6>Thông tin khách hàng</h6>
                                                                <p><strong>Tên:</strong> <?php echo $booking['user_name']; ?></p>
                                                                <p><strong>Email:</strong> <?php echo $booking['email']; ?></p>
                                                                <p><strong>SĐT:</strong> <?php echo $booking['phone']; ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Thông tin đặt sân</h6>
                                                                <p><strong>Sân:</strong> <?php echo $booking['field_name']; ?></p>
                                                                <p><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?></p>
                                                                <p><strong>Thời gian:</strong> <?php echo date('H:i', strtotime($booking['start_time'])) ?></p>
                                                            </div>
                                                        </div>

                                                        <div class="price-details">
                                                            <h6>Chi tiết giá</h6>
                                                            <table class="table table-bordered">
                                                                <tr class="table-success">
                                                                    <th>Tổng cộng</th>
                                                                    <th><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>Ảnh bill thanh toán cọc</td>
                                                                    <td><img width="300px; height: 300px" src="../assets/bill/<?php echo $booking['payment_image']; ?>" alt="<?php echo $booking['user_name']; ?>"></td>
                                                                </tr>
                                                            </table>
                                                        </div>

                                                        <?php if ($booking['note']): ?>
                                                        <div class="booking-note">
                                                            <h6>Ghi chú</h6>
                                                            <p class="text-muted"><?php echo $booking['note']; ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="9" class="text-center">Chưa có đơn đặt sân định kỳ nào.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Thêm hàm helper
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

// Thêm xử lý PHP ở đầu file
if (isset($_POST['confirm_recurring'])) {
    $recurring_id = $_POST['recurring_id'];
    
    $update_query = mysqli_query($conn, "UPDATE recurring_bookings SET status = 'Đã xác nhận' WHERE id = '$recurring_id'");
    
    if ($update_query) {
        $message[] = 'Xác nhận đơn đặt sân định kỳ thành công!';
    } else {
        $message[] = 'Xác nhận đơn đặt sân định kỳ thất bại!';
    }
}

if (isset($_POST['cancel_recurring'])) {
    $recurring_id = $_POST['recurring_id'];
    $cancel_reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']);
    
    if (isset($_FILES['refund_image']) && $_FILES['refund_image']['error'] === 0) {
        $image = $_FILES['refund_image'];
        $image_name = time() . '_refund_' . $image['name'];
        $target_path = '../assets/refund/' . $image_name;

        if (move_uploaded_file($image['tmp_name'], $target_path)) {
            $update_query = mysqli_query($conn, "UPDATE recurring_bookings SET 
                status = 'Đã hủy',
                cancel_reason = '$cancel_reason',
                refund_image = '$image_name',
                cancel_date = NOW()
                WHERE id = '$recurring_id'");

            if ($update_query) {
                $message[] = 'Hủy đơn đặt sân định kỳ thành công!';
            } else {
                $message[] = 'Hủy đơn đặt sân định kỳ thất bại!';
                unlink($target_path);
            }
        } else {
            $message[] = 'Lỗi upload ảnh hoàn tiền!';
        }
    } else {
        $message[] = 'Vui lòng upload ảnh bill hoàn cọc!';
    }
}
?>