<?php
include 'database/DBController.php';
session_start();

$user_id = @$_SESSION['user_id'] ?? null;
$field_id = $_GET['id'] ?? null;

if (!$field_id) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin sân bóng
$field_query = mysqli_query($conn, "SELECT * FROM football_fields WHERE id = '$field_id'") or die('Query failed');
$field = mysqli_fetch_assoc($field_query);

// Xử lý đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    if (!$user_id) {
        header('location:./login.php');
        exit();
    } else {
        $rating = $_POST['rating'];
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        
        $insert_feedback = mysqli_query($conn, "INSERT INTO field_feedback 
            (field_id, user_id, rating, comment) 
            VALUES ('$field_id', '$user_id', '$rating', '$comment')") or die('Query failed');
        
        if ($insert_feedback) {
            $message[] = 'Cảm ơn bạn đã đánh giá!';
        }
    }
}

// Lấy danh sách đánh giá
$feedback_query = mysqli_query($conn, "
    SELECT f.*, u.username 
    FROM field_feedback f 
    JOIN users u ON f.user_id = u.user_id 
    WHERE f.field_id = '$field_id' 
    ORDER BY f.created_at DESC
") or die('Query failed');

// Tính rating trung bình
$avg_rating_query = mysqli_query($conn, "
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
    FROM field_feedback 
    WHERE field_id = '$field_id'
") or die('Query failed');
$rating_stats = mysqli_fetch_assoc($avg_rating_query);
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <!-- Thông tin sân bóng -->
        <div class="col-lg-8">
            <div class="field-detail-card">
                <div class="field-image">
                    <img src="assets/fields/<?php echo $field['image']; ?>" alt="<?php echo $field['name']; ?>" class="img-fluid">
                </div>
                <div class="field-info mt-4">
                    <h2><?php echo $field['name']; ?></h2>
                    <div class="rating-summary mb-3">
                        <div class="stars">
                            <?php
                            $avg_rating = round($rating_stats['avg_rating'], 1);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    echo '<i class="fas fa-star text-warning"></i>';
                                } else {
                                    echo '<i class="far fa-star text-warning"></i>';
                                }
                            }
                            ?>
                            <span class="ms-2"><?php echo $avg_rating; ?>/5 
                                (<?php echo $rating_stats['total_ratings']; ?> đánh giá)</span>
                        </div>
                    </div>
                    <div class="field-details">
                        <p><i class="fas fa-futbol"></i> Sân <?php echo $field['field_type']; ?> người</p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo $field['address']; ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo $field['phone_number']; ?></p>
                        <p><i class="fas fa-info-circle"></i> <?php echo $field['description']; ?></p>
                        <p class="price">
                            <i class="fas fa-money-bill"></i> 
                            <?php echo number_format($field['rental_price'], 0, ',', '.'); ?> đ/giờ
                        </p>
                        <!-- <p class="status">
                            <i class="fas fa-circle <?php echo $field['status'] == 'Đang trống' ? 'text-success' : 'text-danger'; ?>"></i>
                            <?php echo $field['status']; ?>
                        </p> -->
                    </div>
                    <div class="booking-buttons mt-3">
                        <a href="booking.php?field_id=<?php echo $field['id']; ?>" 
                           class="btn btn-success btn-lg">
                            <i style="color: white;" class="fas fa-calendar-alt"></i> Đặt sân ngay
                        </a>
                        <a href="recurring-booking.php?field_id=<?php echo $field['id']; ?>" 
                           class="btn btn-primary btn-lg ms-2">
                            <i style="color: white;" class="fas fa-calendar-week"></i> Đặt sân định kỳ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form đánh giá -->
        <div class="col-lg-4">
            <div class="feedback-section">
                <h3>Đánh giá sân bóng</h3>
                <form method="POST" class="feedback-form">
                    <div class="rating-input mb-3">
                        <p>Chọn đánh giá của bạn:</p>
                        <div class="star-rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                            <label for="star<?php echo $i; ?>"><i class="far fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <textarea name="comment" class="form-control" rows="4" 
                                placeholder="Nhập đánh giá của bạn..." required></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="btn btn-primary">
                        Gửi đánh giá
                    </button>
                </form>

                <!-- Danh sách đánh giá -->
                <div class="feedback-list mt-4">
                    <h4>Đánh giá từ người dùng</h4>
                    <?php while($feedback = mysqli_fetch_assoc($feedback_query)): ?>
                    <div class="feedback-item">
                        <div class="user-info">
                            <strong><?php echo $feedback['username']; ?></strong>
                            <div class="rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $feedback['rating']) {
                                        echo '<i class="fas fa-star text-warning"></i>';
                                    } else {
                                        echo '<i class="far fa-star text-warning"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <p class="comment"><?php echo $feedback['comment']; ?></p>
                        <small class="text-muted">
                            <?php echo date('d/m/Y H:i', strtotime($feedback['created_at'])); ?>
                        </small>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.field-detail-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 20px;
}

.field-image img {
    width: 100%;
    border-radius: 10px;
    height: 400px;
    object-fit: cover;
}

.field-info i {
    width: 25px;
    color: #28a745;
}

.feedback-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 20px;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    font-size: 25px;
    color: #ddd;
    margin: 0 2px;
}

.star-rating input:checked ~ label,
.star-rating input:hover ~ label {
    color: #ffc107;
}

.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffc107;
}

.feedback-item {
    border-bottom: 1px solid #eee;
    padding: 15px;
    background-color: #f8f9fa;
    margin-bottom: 8px;
    border-radius: 8px;
}

.feedback-item:last-child {
    border-bottom: none;
}

.user-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.comment {
    margin: 10px 0;
}
</style>

<?php include 'footer.php'; ?>