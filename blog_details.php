<?php
ob_start();
session_start();

// include header.php file
include('header.php');
include './database/DBController.php';

?>
<?php
// Lấy thông tin blog theo ID
$blog_id = $_GET['id'] ?? 0; // Lấy ID từ URL
$blog = null;

$stmt = $conn->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $blog = $result->fetch_assoc();
} else {
    header('location: blog.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $blog['title']; ?> - Blog Details</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .blog-details-container {
            max-width: 800px;
            margin: 50px auto;
        }

        .blog-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .blog-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .blog-description {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
        }

        .back-to-list {
            text-decoration: none;
            font-size: 1rem;
            color: #007bff;
        }

        .back-to-list:hover {
            text-decoration: underline;
            color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container blog-details-container">
        <a href="blog.php" class="back-to-list">&larr; Quay lại</a>
        <h1 class="blog-title"><?php echo $blog['title']; ?></h1>
        <img src="./assets/blog/<?php echo $blog['image']; ?>" alt="<?php echo $blog['title']; ?>" class="blog-image">
        <p class="blog-description"><?php echo $blog['description']; ?></p>
    </div>
</body>

</html>
<?php

/*  include cart items if it is not empty */
include('./message.php');


?>

<?php
// include footer.php file
include('footer.php');
?>