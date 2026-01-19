<?php
include '../database/DBController.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// Thêm sân bóng mới
if (isset($_POST['add_field'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $field_type = $_POST['field_type'];
    $rental_price = mysqli_real_escape_string($conn, $_POST['rental_price']);
    $status = $_POST['status'];
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);

    // Upload hình ảnh sân bóng
    $image_name = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = '../assets/fields/' . $image_name;

    if (move_uploaded_file($image_tmp_name, $image_folder)) {
        $insert_field_query = mysqli_query($conn, "INSERT INTO `football_fields` (name, address, description, field_type, rental_price, status, image, phone_number) 
        VALUES ('$name', '$address', '$description', '$field_type', '$rental_price', '$status', '$image_name', '$phone_number')") or die('Query failed');

        if ($insert_field_query) {
            $message[] = 'Thêm sân bóng thành công!';
        } else {
            $message[] = 'Thêm sân bóng thất bại!';
        }
    } else {
        $message[] = 'Lỗi khi tải ảnh!';
    }
}

// Xóa sân bóng
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Tắt kiểm tra khóa ngoại
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

    try {
        // Xóa các đặt sân định kỳ liên quan
        mysqli_query($conn, "DELETE FROM recurring_bookings WHERE field_id = '$delete_id'");

        // Xóa các đặt sân thường liên quan  
        mysqli_query($conn, "DELETE FROM bookings WHERE field_id = '$delete_id'");

        // Lấy tên ảnh để xóa file
        $delete_image_query = mysqli_query($conn, "SELECT image FROM `football_fields` WHERE id = '$delete_id'");
        $fetch_image = mysqli_fetch_assoc($delete_image_query);
        
        // Xóa sân bóng
        $delete_query = mysqli_query($conn, "DELETE FROM `football_fields` WHERE id = '$delete_id'");

        if ($delete_query) {
            // Xóa file ảnh nếu tồn tại
            if($fetch_image['image']) {
                @unlink('../assets/fields/'.$fetch_image['image']);
            }
            $message[] = 'Xóa sân bóng thành công!';
        } else {
            $message[] = 'Xóa sân bóng thất bại!';
        }

    } catch (Exception $e) {
        $message[] = 'Xóa sân bóng thất bại!';
    }

    // Bật lại kiểm tra khóa ngoại
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

    header('location: admin_products.php');
    exit();
}

// Cập nhật sân bóng
if (isset($_POST['update_field'])) {
    $update_id = $_POST['update_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $field_type = $_POST['field_type'];
    $rental_price = mysqli_real_escape_string($conn, $_POST['rental_price']);
    $status = $_POST['status'];
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);

    $update_query = "UPDATE `football_fields` SET 
                    name = '$name', 
                    address = '$address',
                    description = '$description',
                    field_type = '$field_type',
                    rental_price = '$rental_price',
                    status = '$status',
                    phone_number = '$phone_number'";

    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../assets/fields/' . $image_name;

        move_uploaded_file($image_tmp_name, $image_folder);
        $update_query .= ", image = '$image_name'";
    }

    $update_query .= " WHERE id = '$update_id'";
    $update_result = mysqli_query($conn, $update_query) or die('Query failed');

    if ($update_result) {
        $message[] = 'Cập nhật sân bóng thành công!';
    } else {
        $message[] = 'Cập nhật sân bóng thất bại!';
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$total_fields_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `football_fields`") or die('Query failed');
$total_fields = mysqli_fetch_assoc($total_fields_query)['total'];

$select_fields = mysqli_query($conn, "SELECT * FROM `football_fields` ORDER BY id DESC LIMIT $limit OFFSET $offset") or die('Query failed');

$total_pages = ceil($total_fields / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sân bóng</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="./css/admin_style.css">
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
                <h1 class="mb-0">Quản lý Sân bóng</h1>
            </div>
            <section class="add-products mb-4">
                <form action="" method="post" enctype="multipart/form-data">
                    <h3>Thêm sân bóng mới</h3>
                    <div class="mb-3">
                        <input type="text" name="name" class="form-control" placeholder="Tên sân bóng" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="address" class="form-control" placeholder="Địa chỉ" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="description" class="form-control" placeholder="Mô tả" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <select name="field_type" class="form-control" required>
                            <option value="5">Sân 5</option>
                            <option value="7">Sân 7</option>
                            <option value="11">Sân 11</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="rental_price" class="form-control" placeholder="Giá thuê" required>
                    </div>
                    <div class="mb-3">
                        <select name="status" class="form-control" required>
                            <option value="Đang trống">Đang trống</option>
                            <option value="Đã đặt">Đã đặt</option>
                            <option value="Bảo trì">Bảo trì</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="phone_number" class="form-control" placeholder="Số điện thoại">
                    </div>
                    <div class="mb-3">
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" name="add_field" class="btn btn-primary">Thêm sân bóng</button>
                </form>
            </section>

            <section class="show-fields">
                <div class="container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hình ảnh</th>
                                <th>Tên sân</th>
                                <th>Loại sân</th>
                                <th>Giá thuê</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if (mysqli_num_rows($select_fields) > 0) {
                                while ($field = mysqli_fetch_assoc($select_fields)) {
                            ?>
                                    <tr>
                                        <td><?php echo $field['id']; ?></td>
                                        <td><img src="../assets/fields/<?php echo $field['image']; ?>" width="50" alt=""></td>
                                        <td><?php echo $field['name']; ?></td>
                                        <td>Sân <?php echo $field['field_type']; ?></td>
                                        <td><?php echo number_format($field['rental_price'], 0, ',', '.'); ?> đ</td>
                                        <td><?php echo $field['status']; ?></td>
                                        <td>
                                            <!-- Modal trigger button -->
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $field['id']; ?>">Sửa</button>
                                            <!-- Modal -->
                                            <div class="modal fade" id="editModal<?php echo $field['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel">Sửa sân bóng</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="post" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="update_id" value="<?php echo $field['id']; ?>">
                                                                <input type="text" name="name" class="form-control mb-3" value="<?php echo $field['name']; ?>" required>
                                                                <input type="text" name="address" class="form-control mb-3" value="<?php echo $field['address']; ?>" required>
                                                                <textarea name="description" class="form-control mb-3" rows="3" required><?php echo $field['description']; ?></textarea>
                                                                <select name="field_type" class="form-control mb-3" required>
                                                                    <option value="5" <?php echo $field['field_type'] == 5 ? 'selected' : ''; ?>>Sân 5</option>
                                                                    <option value="7" <?php echo $field['field_type'] == 7 ? 'selected' : ''; ?>>Sân 7</option>
                                                                    <option value="11" <?php echo $field['field_type'] == 11 ? 'selected' : ''; ?>>Sân 11</option>
                                                                </select>
                                                                <input type="number" name="rental_price" class="form-control mb-3" value="<?php echo $field['rental_price']; ?>" required>
                                                                <select name="status" class="form-control mb-3" required>
                                                                    <option value="Đang trống" <?php echo $field['status'] == 'Đang trống' ? 'selected' : ''; ?>>Đang trống</option>
                                                                    <option value="Đã đặt" <?php echo $field['status'] == 'Đã đặt' ? 'selected' : ''; ?>>Đã đặt</option>
                                                                    <option value="Bảo trì" <?php echo $field['status'] == 'Bảo trì' ? 'selected' : ''; ?>>Bảo trì</option>
                                                                </select>
                                                                <input type="text" name="phone_number" class="form-control mb-3" value="<?php echo $field['phone_number']; ?>">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                <button type="submit" name="update_field" class="btn btn-primary">Cập nhật</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Modal -->
                                            <a href="?delete=<?php echo $field['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sân bóng này?');">Xóa</a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center">Chưa có sân bóng nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php } ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>