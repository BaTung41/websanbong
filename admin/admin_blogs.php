<?php
include '../database/DBController.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// Thêm blog mới
if (isset($_POST['add_blog'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Upload hình ảnh blog
    $image_name = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = '../assets/blog/' . $image_name;

    if (move_uploaded_file($image_tmp_name, $image_folder)) {
        $insert_blog_query = mysqli_query($conn, "INSERT INTO `blogs` (title, description, image) 
        VALUES ('$title', '$description', '$image_name')") or die('Query failed');

        if ($insert_blog_query) {
            $message[] = 'Thêm blog thành công!';
        } else {
            $message[] = 'Thêm blog thất bại!';
        }
    } else {
        $message[] = 'Lỗi khi tải ảnh!';
    }
}

// Xóa blog
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_image_query = mysqli_query($conn, "SELECT image FROM `blogs` WHERE id = '$delete_id'") or die('Query failed');
    $fetch_image = mysqli_fetch_assoc($delete_image_query);

    $delete_query = mysqli_query($conn, "DELETE FROM `blogs` WHERE id = '$delete_id'") or die('Query failed');

    if ($delete_query) {
        $message[] = 'Xóa blog thành công!';
    } else {
        $message[] = 'Xóa blog thất bại!';
    }
}

// Cập nhật blog
if (isset($_POST['update_blog'])) {
    $update_id = $_POST['update_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $update_query = "UPDATE `blogs` SET title = '$title', description = '$description'";

    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../assets/blog/' . $image_name;

        move_uploaded_file($image_tmp_name, $image_folder);
        $update_query .= ", image = '$image_name'";
    }
    $update_query .= " WHERE id = '$update_id'";
    $update_result = mysqli_query($conn, $update_query) or die('Query failed');

    if ($update_result) {
        $message[] = 'Cập nhật blog thành công!';
    } else {
        $message[] = 'Cập nhật blog thất bại!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý blog</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/admin_style.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
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
                <h1 class="mb-0">Quản lý Blog</h1>
            </div>
            <section class="add-products mb-4">
                <form action="" method="post" enctype="multipart/form-data">
                    <h3>Thêm blog mới</h3>
                    <div class="mb-3">
                        <input type="text" name="title" class="form-control" placeholder="Tiêu đề blog" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="description" id="description" class="form-control" placeholder="Mô tả blog" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" name="add_blog" class="btn btn-primary">Thêm blog</button>
                </form>
            </section>

            <section class="show-blogs">
                <div class="container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hình ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $select_blogs = mysqli_query($conn, "SELECT * FROM `blogs` ORDER BY id DESC") or die('Query failed');
                            if (mysqli_num_rows($select_blogs) > 0) {
                                while ($blog = mysqli_fetch_assoc($select_blogs)) {
                            ?>
                                    <tr>
                                        <td><?php echo $blog['id']; ?></td>
                                        <td><img src="../assets/blog/<?php echo $blog['image']; ?>" alt="" width="90"></td>
                                        <td><?php echo $blog['title']; ?></td>
                                        <td>
                                            <!-- Modal trigger button -->
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $blog['id']; ?>">Sửa</button>
                                            <!-- Modal -->
                                            <div class="modal fade" id="editModal<?php echo $blog['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel">Sửa blog</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="post" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="update_id" value="<?php echo $blog['id']; ?>">
                                                                <input type="text" name="title" class="form-control mb-3" value="<?php echo $blog['title']; ?>" required>
                                                                <textarea name="description" id="description<?php echo $blog['id']; ?>" class="form-control mb-3" rows="5" required><?php echo $blog['description']; ?></textarea>
                                                                <input type="file" name="image" class="form-control mb-3" accept="image/*">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                <button type="submit" name="update_blog" class="btn btn-primary">Cập nhật</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Modal -->
                                            <a href="admin_blogs.php?delete=<?php echo $blog['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa blog này?');">Xóa</a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">Chưa có blog nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Khởi tạo CKEditor cho form thêm mới
        CKEDITOR.replace('description');
        
        // Khởi tạo CKEditor cho các form sửa (nếu có)
        <?php if (isset($select_blogs) && mysqli_num_rows($select_blogs) > 0) { 
            while ($blog = mysqli_fetch_assoc($select_blogs)) { ?>
            CKEDITOR.replace('description<?php echo $blog['id']; ?>');
        <?php } } ?>
    </script>
</body>

</html>
