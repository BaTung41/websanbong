<!-- Football Fields -->
<?php
$user_id = @$_SESSION['user_id'] ?? null;

// Lấy danh sách sân bóng
$select_fields = mysqli_query($conn, "SELECT * FROM `football_fields` ORDER BY id DESC") or die('Query failed');
$fields = mysqli_fetch_all($select_fields, MYSQLI_ASSOC);
?>

<section id="football-fields">
    <div class="container py-5">
        <h4 class="font-rubik font-size-20">Danh Sách Sân Bóng</h4>
        <hr>
        <!-- owl carousel -->
        <div class="owl-carousel owl-theme">
            <?php foreach ($fields as $field) { ?>
            <div class="item py-2 mr-4">
                <div class="field-card font-rale">
                    <a href="<?php printf('%s?field_id=%s', 'field-detail.php', $field['id']); ?>">
                        <img src="./assets/fields/<?php echo $field['image'] ?? 'default.jpg'; ?>" alt="<?php echo $field['name']; ?>" class="img-fluid">
                    </a>
                    <div class="text-center">
                        <h6 class="field-name"><?php echo $field['name']; ?></h6>
                        <div class="field-info py-2">
                            <p class="mb-1">
                                <i class="fas fa-futbol"></i> 
                                Sân <?php echo $field['field_type']; ?> người
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo substr($field['address'], 0, 50) . '...'; ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-phone"></i>
                                <?php echo $field['phone_number']; ?>
                            </p>
                        </div>
                        <div class="price-status py-2">
                            <div class="price mb-2">
                                <span class="text-success font-weight-bold">
                                    <?php echo number_format($field['rental_price'], 0, ',', '.'); ?> đ/giờ
                                </span>
                            </div>
                            <!-- <div class="status">
                                <?php 
                                $statusClass = '';
                                switch($field['status']) {
                                    case 'Đang trống':
                                        $statusClass = 'text-success';
                                        break;
                                    case 'Đã đặt':
                                        $statusClass = 'text-danger';
                                        break;
                                    case 'Bảo trì':
                                        $statusClass = 'text-warning';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $statusClass; ?>">
                                    <i class="fas fa-circle"></i> 
                                    <?php echo $field['status']; ?>
                                </span>
                            </div> -->
                        </div>
                        <div class="field-actions">
                            <a href="field-detail.php?id=<?php echo $field['id']; ?>" 
                            class="btn btn-info font-size-12">
                                <i class="fas fa-info-circle"></i> Xem chi tiết
                            </a>
                           
                            <a href="booking.php?field_id=<?php echo $field['id']; ?>" class="btn btn-success font-size-12 mt-2">
                                Đặt sân
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
        <!-- !owl carousel -->
    </div>
</section>

<style>
.field-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    padding: 10px;
    margin: 10px;
}

.field-card:hover {
    transform: translateY(-5px);
}

.field-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.field-name {
    font-size: 18px;
    font-weight: 600;
    margin: 15px 0;
    height: 40px;
    overflow: hidden;
}

.field-info {
    text-align: left;
    padding: 0 10px;
}

.field-info p {
    font-size: 14px;
    color: #666;
}

.field-info i {
    width: 20px;
    color: #28a745;
}

.price-status {
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    padding: 10px 0;
    margin: 10px 0;
}

.price span {
    font-size: 18px;
}

.status i {
    font-size: 10px;
    margin-right: 5px;
}

.btn {
    padding: 8px 20px;
    border-radius: 5px;
    font-weight: 500;
}

.btn i {
    margin-right: 5px;
}

/* Owl Carousel Custom Navigation */
.owl-nav button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.8) !important;
    border-radius: 50% !important;
    width: 40px;
    height: 40px;
}

.owl-prev {
    left: -20px;
}

.owl-next {
    right: -20px;
}

.owl-nav button span {
    font-size: 24px;
    line-height: 0;
}
</style>

<!-- Initialize Owl Carousel -->
<script>
$(document).ready(function(){
    $("#football-fields .owl-carousel").owlCarousel({
        loop: true,
        nav: true,
        dots: false,
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 2
            },
            1000: {
                items: 4
            }
        }
    });
});
</script>
<!-- !Football Fields -->