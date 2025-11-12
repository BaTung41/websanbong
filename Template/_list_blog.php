
<?php
// Lấy danh sách blogs
$blogs = [];
$result = $conn->query("SELECT * FROM blogs");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $blogs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Blog</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .blog-card {
            margin-top: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s ease-in-out;
        }

        .blog-card:hover {
            transform: scale(1.02);
        }

        .blog-card img {
            object-fit: cover;
            height: 200px;
        }

        .blog-card .card-body {
            padding: 20px;
        }

        .blog-card-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .blog-card-description {
            overflow: hidden; /* Ẩn nội dung tràn */
            text-overflow: ellipsis; /* Thêm dấu ... */
            display: -webkit-box; /* Kích hoạt chế độ hộp linh hoạt */
            -webkit-line-clamp: 3; /* Hiển thị tối đa 3 dòng */
            -webkit-box-orient: vertical; /* Đặt hướng hộp thành dọc */
            font-size: 1rem;
            color: #555;
        }

        .btn-read-more {
            background-color: #007bff;
            color: #fff;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn-read-more:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Danh sách Blog</h1>
        <div class="row g-4">
            <?php foreach ($blogs as $blog): ?>
                <div class="col-md-4">
                    <div class="card blog-card">
                        <img src="./assets/blog/<?php echo $blog['image']; ?>" class="card-img-top" alt="<?php echo $blog['title']; ?>">
                        <div class="card-body">
                            <h5 class="card-title blog-card-title"><?php echo $blog['title']; ?></h5>
                            <p class="card-text blog-card-description"><?php echo $blog['description']; ?></p>
                            <a href="blog_details.php?id=<?php echo $blog['id']; ?>" class="btn btn-read-more">Đọc thêm</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>