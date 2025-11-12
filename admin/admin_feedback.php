<?php
include '../database/DBController.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// Xóa feedback
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_query = mysqli_query($conn, "DELETE FROM `field_feedback` WHERE id = '$delete_id'") or die('Query failed');

    if ($delete_query) {
        $message[] = 'Xóa đánh giá thành công!';
    } else {
        $message[] = 'Xóa đánh giá thất bại!';
    }
}

// Ẩn/Hiện feedback
if (isset($_GET['toggle_status'])) {
    $feedback_id = $_GET['toggle_status'];
    $status = $_GET['status'];
    $new_status = $status == 1 ? 0 : 1;
    
    $update_query = mysqli_query($conn, "UPDATE `field_feedback` SET status = '$new_status' WHERE id = '$feedback_id'") or die('Query failed');

    if ($update_query) {
        $message[] = $new_status == 1 ? 'Hiện đánh giá thành công!' : 'Ẩn đánh giá thành công!';
    } else {
        $message[] = 'Cập nhật trạng thái thất bại!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đánh giá</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .star-rating {
            color: #ffc107;
        }
        .feedback-stats {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .rating-bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .rating-fill {
            height: 100%;
            background-color: #28a745;
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
                        <span style="font-size: 16px;">' . $msg . '</span>
                        <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
                    </div>';
                }
            }
            ?>
            <div style="background-color: #28a745" class="text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Quản lý Đánh giá</h1>
            </div>

            <!-- Thống kê đánh giá -->
            <div class="container mb-4">
                <div class="feedback-stats">
                    <h4>Thống kê đánh giá</h4>
                    <?php
                    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM field_feedback");
                    $total = mysqli_fetch_assoc($total_query)['total'];

                    for ($i = 5; $i >= 1; $i--) {
                        $rating_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM field_feedback WHERE rating = $i");
                        $rating_count = mysqli_fetch_assoc($rating_query)['count'];
                        $percentage = $total > 0 ? ($rating_count / $total) * 100 : 0;
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?php echo $i ?> sao (<?php echo $rating_count ?>)</span>
                            <span><?php echo round($percentage, 1) ?>%</span>
                        </div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?php echo $percentage ?>%"></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <section class="show-feedback">
                <div class="container">
                    <!-- Filter -->
                    <div class="mb-3">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="rating" class="form-select">
                                    <option value="">Tất cả đánh giá</option>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i ?>" <?php echo isset($_GET['rating']) && $_GET['rating'] == $i ? 'selected' : '' ?>>
                                            <?php echo $i ?> sao
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Lọc</button>
                            </div>
                        </form>
                    </div>

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sân</th>
                                <th>Người đánh giá</th>
                                <th>Đánh giá</th>
                                <th>Nội dung</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where_clause = "";
                            if(isset($_GET['rating']) && $_GET['rating'] != '') {
                                $rating = $_GET['rating'];
                                $where_clause = "WHERE ff.rating = '$rating'";
                            }

                            $select_feedback = mysqli_query($conn, "
                                SELECT ff.*, f.name as field_name, u.username 
                                FROM field_feedback ff
                                JOIN football_fields f ON ff.field_id = f.id
                                JOIN users u ON ff.user_id = u.user_id
                                $where_clause
                                ORDER BY ff.created_at DESC
                            ") or die('Query failed');

                            if (mysqli_num_rows($select_feedback) > 0) {
                                while ($feedback = mysqli_fetch_assoc($select_feedback)) {
                            ?>
                                    <tr>
                                        <td><?php echo $feedback['id']; ?></td>
                                        <td><?php echo $feedback['field_name']; ?></td>
                                        <td><?php echo $feedback['username']; ?></td>
                                        <td>
                                            <div class="star-rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $feedback['rating']) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td><?php echo $feedback['comment']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($feedback['created_at'])); ?></td>
                                        <td>
                                            <a href="admin_feedback.php?delete=<?php echo $feedback['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?');">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                            <a href="admin_feedback.php?toggle_status=<?php echo $feedback['id']; ?>&status=<?php echo $feedback['status']; ?>" 
                                               class="btn <?php echo $feedback['status'] == 1 ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                                <i class="fas <?php echo $feedback['status'] == 1 ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                <?php echo $feedback['status'] == 1 ? 'Ẩn' : 'Hiện'; ?>
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center">Chưa có đánh giá nào.</td></tr>';
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