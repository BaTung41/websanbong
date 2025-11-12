<?php
include '../database/DBController.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// Xóa tài khoản người dùng
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    // Tắt kiểm tra khóa ngoại
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

    // Xóa tài khoản người dùng
    $delete_query = mysqli_query($conn, "DELETE FROM `users` WHERE user_id = '$delete_id'");

    // Bật lại kiểm tra khóa ngoại
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

    if ($delete_query) {
        $message[] = 'Xóa tài khoản người dùng thành công!';
    } else {
        $message[] = 'Xóa tài khoản người dùng thất bại!';
    }

    header('location:admin_accounts.php');
    exit();
}

// Khóa tài khoản người dùng
if (isset($_GET['block'])) {
    $block_id = $_GET['block'];

    // Khóa tài khoản người dùng
    $block_query = mysqli_query($conn, "UPDATE `users` SET status = '0' WHERE user_id = '$block_id'") or die('Query failed');

    if ($block_query) {
        $message[] = 'Khóa tài khoản người dùng thành công!';
    } else {
        $message[] = 'Khóa tài khoản người dùng thất bại!';
    }

}

// Mở khóa tài khoản người dùng
if (isset($_GET['un_block'])) {
    $un_block_id = $_GET['un_block'];

    // Mở khóa tài khoản người dùng
    $un_block_query = mysqli_query($conn, "UPDATE `users` SET status = '1' WHERE user_id = '$un_block_id'") or die('Query failed');

    if ($un_block_query) {
        $message[] = 'Mở khóa tài khoản người dùng thành công!';
    } else {
        $message[] = 'Mở khóa tài khoản người dùng thất bại!';
    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        th {
            background-color: #86eb86 !important;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
            <?php
            //nhúng vào các trang bán hàng
            if (isset($message)) { // hiển thị thông báo sau khi thao tác với biến message được gán giá trị
                foreach ($message as $msg) {
                    echo '
                    <div class=" alert alert-info alert-dismissible fade show" role="alert">
                        <span style="font-size: 16px;">' . $msg . '</span>
                        <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
                    </div>';
                }
            }
            ?>
            <div style="background-color: #28a745" class="text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Quản lý Tài khoản</h1>
            </div>
            <section  class="show-users">
                <div class="container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $select_users = mysqli_query($conn, "SELECT * FROM `users` where role ='user' ORDER BY user_id DESC") or die('Query failed');
                            if (mysqli_num_rows($select_users) > 0) {
                                while ($user = mysqli_fetch_assoc($select_users)) {
                            ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <?php echo $user['role']; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] == 1) { ?>
                                                <a href="admin_accounts.php?block=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?');">Khóa Tài Khoản</a>
                                            <?php } else { ?>
                                                <a href="admin_accounts.php?un_block=<?php echo $user['user_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?');">Mở Khóa Tài Khoản</a>
                                            <?php } ?>
                                            <a href="admin_accounts.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">Xóa</a>
                                            
                                            <!-- Thêm nút xem chi tiết -->
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $user['user_id']; ?>">
                                                Xem chi tiết
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" id="userModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="userModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="userModalLabel<?php echo $user['user_id']; ?>">
                                                                Thông tin chi tiết người dùng
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="user-info">
                                                                <p><strong>ID:</strong> <?php echo $user['user_id']; ?></p>
                                                                <p><strong>Tên đăng nhập:</strong> <?php echo $user['username']; ?></p>
                                                                <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                                                                <p><strong>Trạng thái:</strong> 
                                                                    <?php echo ($user['status'] == 1) ? '<span class="text-success">Đang hoạt động</span>' : '<span class="text-danger">Đã khóa</span>'; ?>
                                                                </p>
                                                                <p><strong>Ngày tạo:</strong> <?php echo $user['created_at']; ?></p>
                                                                
                                                                <!-- Thống kê đơn hàng -->
                                                                <?php
                                                                $user_id = $user['user_id'];
                                                                // Thống kê từ bảng bookings
                                                                $bookings_query = mysqli_query($conn, "SELECT 
                                                                    COUNT(*) as total_bookings,
                                                                    SUM(CASE WHEN status = 'Đã xác nhận' THEN 1 ELSE 0 END) as confirmed_bookings,
                                                                    SUM(CASE WHEN status = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled_bookings,
                                                                    SUM(CASE WHEN status = 'Đã xác nhận' THEN total_price ELSE 0 END) as total_spent_bookings
                                                                    FROM `bookings` 
                                                                    WHERE user_id = '$user_id'") or die('Bookings query failed');
                                                                $bookings_stats = mysqli_fetch_assoc($bookings_query);

                                                                // Thống kê từ bảng recurring_bookings
                                                                $recurring_query = mysqli_query($conn, "SELECT 
                                                                    COUNT(*) as total_recurring,
                                                                    SUM(CASE WHEN status = 'Đã xác nhận' THEN 1 ELSE 0 END) as confirmed_recurring,
                                                                    SUM(CASE WHEN status = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled_recurring,
                                                                    SUM(CASE WHEN status = 'Đã xác nhận' THEN total_price ELSE 0 END) as total_spent_recurring
                                                                    FROM `recurring_bookings` 
                                                                    WHERE user_id = '$user_id'") or die('Recurring query failed');
                                                                $recurring_stats = mysqli_fetch_assoc($recurring_query);

                                                                // Tổng hợp thống kê
                                                                $total_orders = $bookings_stats['total_bookings'] + $recurring_stats['total_recurring'];
                                                                $completed_orders = $bookings_stats['confirmed_bookings'] + $recurring_stats['confirmed_recurring'];
                                                                $cancelled_orders = $bookings_stats['cancelled_bookings'] + $recurring_stats['cancelled_recurring'];
                                                                $total_spent = $bookings_stats['total_spent_bookings'] + $recurring_stats['total_spent_recurring'];
                                                                ?>
                                                                <hr>
                                                                <h6>Thống kê đặt sân:</h6>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-muted">Đặt sân thường:</h6>
                                                                        <p><strong>Tổng số đơn:</strong> <?php echo $bookings_stats['total_bookings']; ?></p>
                                                                        <p><strong>Đã xác nhận:</strong> <?php echo $bookings_stats['confirmed_bookings']; ?></p>
                                                                        <p><strong>Đã hủy:</strong> <?php echo $bookings_stats['cancelled_bookings']; ?></p>
                                                                        <p><strong>Tổng tiền:</strong> <?php echo number_format($bookings_stats['total_spent_bookings'], 0, ',', '.'); ?> đ</p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-muted">Đặt sân định kỳ:</h6>
                                                                        <p><strong>Tổng số đơn:</strong> <?php echo $recurring_stats['total_recurring']; ?></p>
                                                                        <p><strong>Đã xác nhận:</strong> <?php echo $recurring_stats['confirmed_recurring']; ?></p>
                                                                        <p><strong>Đã hủy:</strong> <?php echo $recurring_stats['cancelled_recurring']; ?></p>
                                                                        <p><strong>Tổng tiền:</strong> <?php echo number_format($recurring_stats['total_spent_recurring'], 0, ',', '.'); ?> đ</p>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-3">
                                                                    <h6 class="text-success">Tổng hợp:</h6>
                                                                    <p><strong>Tổng số đơn đặt sân:</strong> <?php echo $total_orders; ?></p>
                                                                    <p><strong>Tổng đơn đã xác nhận:</strong> <?php echo $completed_orders; ?></p>
                                                                    <p><strong>Tổng đơn đã hủy:</strong> <?php echo $cancelled_orders; ?></p>
                                                                    <p><strong>Tổng chi tiêu:</strong> <?php echo number_format($total_spent, 0, ',', '.'); ?> đ</p>
                                                                </div>
                                                            </div>
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
                                echo '<tr><td colspan="5" class="text-center">Chưa có tài khoản nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>