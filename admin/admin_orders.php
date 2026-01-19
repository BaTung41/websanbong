<?php
include '../database/DBController.php';

session_start();

$admin_id = $_SESSION['admin_id'];

// Khởi tạo mảng message để lưu thông báo
$message = array();

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
        // Nếu xác nhận đơn, sẽ kiểm tra xem ngày đặt đó đã kín hết giờ chưa;
        // chỉ cập nhật trạng thái sân thành "Đã đặt" nếu đã kín.
        // (Việc kiểm tra sẽ thực hiện sau khi cập nhật trạng thái booking để tính cả booking vừa xác nhận)

        $update_query = mysqli_query($conn, "UPDATE bookings SET status = '$new_status' WHERE id = '$booking_id'");

        if ($update_query) {
            // Nếu vừa xác nhận booking, kiểm tra độ kín giờ của ngày đặt
            if ($new_status == 'Đã xác nhận') {
                // Lấy ngày đặt của booking để kiểm tra
                $sel = mysqli_query($conn, "SELECT booking_date FROM bookings WHERE id = '$booking_id'");
                $srow = mysqli_fetch_assoc($sel);
                $booking_date = $srow['booking_date'];

                // Danh sách giờ hoạt động trong ngày
                $allTimes = ['06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00',
                             '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
                             '20:00', '21:00', '22:00'];

                $bookedTimes = [];
                $res = mysqli_query($conn, "SELECT start_time, end_time FROM bookings WHERE field_id = '$field_id' AND booking_date = '$booking_date' AND status IN ('Chờ xác nhận', 'Đã xác nhận')");

                if ($res && mysqli_num_rows($res) > 0) {
                    while ($r = mysqli_fetch_assoc($res)) {
                        $start = strtotime($r['start_time']);
                        $end = strtotime($r['end_time']);
                        $current = $start;
                        while ($current < $end) {
                            $timeStr = date('H:i', $current);
                            if (!in_array($timeStr, $bookedTimes)) {
                                $bookedTimes[] = $timeStr;
                            }
                            $current += 3600;
                        }
                    }
                }

                // Kiểm tra xem tất cả giờ có đều đã được đặt
                $fullyBooked = true;
                foreach ($allTimes as $t) {
                    if (!in_array($t, $bookedTimes)) {
                        $fullyBooked = false;
                        break;
                    }
                }

                if ($fullyBooked) {
                    mysqli_query($conn, "UPDATE football_fields SET status = 'Đã đặt' WHERE id = '$field_id'");
                    $_SESSION['message'][] = 'Sân đã kín giờ trong ngày.';
                } else {
                    $_SESSION['message'][] = 'Sân vẫn còn giờ trống';
                }
            }

            $_SESSION['message'][] = 'Cập nhật trạng thái đơn đặt sân thành công!';
        } else {
            $_SESSION['message'][] = 'Cập nhật trạng thái đơn đặt sân thất bại!';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
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
                $_SESSION['message'][] = 'Đã hủy đơn và lưu thông tin hoàn cọc!';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['message'][] = 'Có lỗi xảy ra khi hủy đơn!';
                // Xóa ảnh nếu cập nhật database thất bại
                unlink($target_path);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $_SESSION['message'][] = 'Lỗi upload ảnh hoàn tiền!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['message'][] = 'Vui lòng upload ảnh bill hoàn cọc!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Xử lý xóa đơn
if(isset($_POST['delete_booking'])) {
    $booking_id = $_POST['booking_id'];
    $field_id = $_POST['field_id'];
    
    // Lấy thông tin ảnh để xóa file
    $select_booking = mysqli_query($conn, "SELECT payment_image FROM bookings WHERE id = '$booking_id'");
    $booking_data = mysqli_fetch_assoc($select_booking);
    
    // Xóa file ảnh bill
    if ($booking_data && $booking_data['payment_image']) {
        $image_path = '../assets/bill/' . $booking_data['payment_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Xóa đơn khỏi database
    $delete_query = mysqli_query($conn, "DELETE FROM bookings WHERE id = '$booking_id'");
    
    if ($delete_query) {
        // Cập nhật trạng thái sân về "Đang trống"
        mysqli_query($conn, "UPDATE football_fields SET status = 'Đang trống' WHERE id = '$field_id'");
        $_SESSION['message'][] = 'Đã xóa đơn đặt sân thành công!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['message'][] = 'Có lỗi xảy ra khi xóa đơn!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Xử lý xóa đơn định kỳ
if(isset($_POST['delete_recurring'])) {
    $recurring_id = $_POST['recurring_id'];
    $field_id = $_POST['field_id'];

    // Lấy thông tin ảnh để xóa file
    $select_recurring = mysqli_query($conn, "SELECT payment_image FROM recurring_bookings WHERE id = '$recurring_id'");
    $recurring_data = mysqli_fetch_assoc($select_recurring);

    // Xóa file ảnh bill
    if ($recurring_data && $recurring_data['payment_image']) {
        $image_path = '../assets/bill/' . $recurring_data['payment_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Xóa đơn định kỳ khỏi database
    $delete_query = mysqli_query($conn, "DELETE FROM recurring_bookings WHERE id = '$recurring_id'");

    if ($delete_query) {
        // Cập nhật trạng thái sân về "Đang trống"
        mysqli_query($conn, "UPDATE football_fields SET status = 'Đang trống' WHERE id = '$field_id'");
        $_SESSION['message'][] = 'Đã xóa đơn đặt sân định kỳ thành công!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['message'][] = 'Có lỗi xảy ra khi xóa đơn định kỳ!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Thêm xử lý PHP ở đầu file
if (isset($_POST['confirm_recurring'])) {
    $recurring_id = $_POST['recurring_id'];
    
    $update_query = mysqli_query($conn, "UPDATE recurring_bookings SET status = 'Đã xác nhận' WHERE id = '$recurring_id'");
    
    if ($update_query) {
        $_SESSION['message'][] = 'Xác nhận đơn đặt sân định kỳ thành công!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['message'][] = 'Xác nhận đơn đặt sân định kỳ thất bại!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
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
                $_SESSION['message'][] = 'Hủy đơn đặt sân định kỳ thành công!';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['message'][] = 'Hủy đơn đặt sân định kỳ thất bại!';
                unlink($target_path);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $_SESSION['message'][] = 'Lỗi upload ảnh hoàn tiền!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['message'][] = 'Vui lòng upload ảnh bill hoàn cọc!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
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
            if (isset($_SESSION['message']) && is_array($_SESSION['message'])) {
                foreach ($_SESSION['message'] as $msg) {
                    echo '
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <span>' . $msg . '</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                }
                unset($_SESSION['message']);
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
                                                        <button type="button" class="btn btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteBookingModal_<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-trash"></i> Xóa đơn
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Xóa đơn -->
                                        <div class="modal fade" id="deleteBookingModal_<?php echo $booking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Xóa đơn đặt sân</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <p><strong>Cảnh báo:</strong> Hành động này sẽ xóa đơn <strong>#<?php echo $booking['id']; ?></strong> của khách <strong><?php echo $booking['user_name']; ?></strong> khỏi hệ thống và không thể khôi phục. Bạn có chắc chắn?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <input type="hidden" name="field_id" value="<?php echo $booking['field_id']; ?>">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" name="delete_booking" class="btn btn-danger">
                                                                <i class="fas fa-trash"></i> Xóa đơn
                                                            </button>
                                                        </div>
                                                    </form>
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
                                    <td><?php echo convertDayToVietnamese($booking['day_of_week']); ?> hàng tuần</td>
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
                                                        <button type="button" class="btn btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteRecurringModal_<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-trash"></i> Xóa đơn
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Xóa đơn định kỳ -->
                                        <div class="modal fade" id="deleteRecurringModal_<?php echo $booking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Xóa đơn đặt sân định kỳ</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <p><strong>Cảnh báo:</strong> Hành động này sẽ xóa đơn định kỳ <strong>#<?php echo $booking['id']; ?></strong> của khách <strong><?php echo $booking['user_name']; ?></strong> khỏi hệ thống và không thể khôi phục. Bạn có chắc chắn?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <input type="hidden" name="recurring_id" value="<?php echo $booking['id']; ?>">
                                                            <input type="hidden" name="field_id" value="<?php echo $booking['field_id']; ?>">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" name="delete_recurring" class="btn btn-danger">
                                                                <i class="fas fa-trash"></i> Xóa đơn
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
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
        'monday' => 'Thứ 2',
        'tuesday' => 'Thứ 3',
        'wednesday' => 'Thứ 4',
        'thursday' => 'Thứ 5',
        'friday' => 'Thứ 6',
        'saturday' => 'Thứ 7',
        'sunday' => 'Chủ nhật'
    ];

    // Nếu truyền vào là số (1-7) theo chuẩn ISO-8601 (1=Mon,7=Sun)
    if (is_numeric($day)) {
        $num = intval($day);
        $numMap = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
        return isset($numMap[$num]) ? $numMap[$num] : $day;
    }

    // Chuẩn hoá chuỗi và dò trong map
    $key = strtolower(trim($day));
    if (isset($dayMap[$key])) {
        return $dayMap[$key];
    }

    // Nếu đã là tiếng Việt (ví dụ 'Thứ 2' hoặc 'Chủ nhật'), trả về nguyên bản
    if (mb_strpos($key, 'thứ') !== false || mb_strpos($key, 'chủ') !== false) {
        return $day;
    }

    // Mặc định trả về giá trị gốc nếu không xác định được
    return $day;
}
?>