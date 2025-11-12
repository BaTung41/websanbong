<?php
include 'database/DBController.php';
session_start();

// Khởi tạo các biến filter từ GET parameters
$date = $_GET['date'] ?? date('Y-m-d');
$time_slot = $_GET['time_slot'] ?? '';
$field_type = $_GET['field_type'] ?? '';
$status = $_GET['status'] ?? '';

// Xây dựng câu query cơ bản
$sql = "SELECT DISTINCT f.* FROM football_fields f 
        WHERE 1=1";
$params = [];
$types = "";

// Filter theo loại sân
if (!empty($field_type)) {
    $sql .= " AND f.field_type = ?";
    $params[] = $field_type;
    $types .= "s";
}

// Filter theo trạng thái sân
if (!empty($status)) {
    if ($status == 'available') {
        // Kiểm tra sân trống trong khung giờ đã chọn
        $sql .= " AND f.id NOT IN (
            SELECT field_id FROM bookings 
            WHERE booking_date = ? 
            AND status != 'Đã hủy'";
        
        if (!empty($time_slot)) {
            switch($time_slot) {
                case 'morning':
                    $sql .= " AND ((start_time >= '06:00' AND start_time < '11:00') 
                             OR (end_time > '06:00' AND end_time <= '11:00'))";
                    break;
                case 'afternoon':
                    $sql .= " AND ((start_time >= '13:00' AND start_time < '17:00') 
                             OR (end_time > '13:00' AND end_time <= '17:00'))";
                    break;
                case 'evening':
                    $sql .= " AND ((start_time >= '17:00' AND start_time < '22:00') 
                             OR (end_time > '17:00' AND end_time <= '22:00'))";
                    break;
            }
        }
        $sql .= ")";
        $params[] = $date;
        $types .= "s";
    } elseif ($status == 'booked') {
        // Kiểm tra sân đã được đặt trong khung giờ đã chọn
        $sql .= " AND f.id IN (
            SELECT field_id FROM bookings 
            WHERE booking_date = ? 
            AND status = 'Đã xác nhận'";
        
        if (!empty($time_slot)) {
            switch($time_slot) {
                case 'morning':
                    $sql .= " AND ((start_time >= '06:00' AND start_time < '11:00') 
                             OR (end_time > '06:00' AND end_time <= '11:00'))";
                    break;
                case 'afternoon':
                    $sql .= " AND ((start_time >= '13:00' AND start_time < '17:00') 
                             OR (end_time > '13:00' AND end_time <= '17:00'))";
                    break;
                case 'evening':
                    $sql .= " AND ((start_time >= '17:00' AND start_time < '22:00') 
                             OR (end_time > '17:00' AND end_time <= '22:00'))";
                    break;
            }
        }
        $sql .= ")";
        $params[] = $date;
        $types .= "s";
    }
} else if (!empty($time_slot)) {
    // Nếu chỉ filter theo khung giờ
    $sql .= " AND f.id NOT IN (
        SELECT field_id FROM bookings 
        WHERE booking_date = ? 
        AND status != 'Đã hủy'";
    
    switch($time_slot) {
        case 'morning':
            $sql .= " AND ((start_time >= '06:00' AND start_time < '11:00') 
                     OR (end_time > '06:00' AND end_time <= '11:00'))";
            break;
        case 'afternoon':
            $sql .= " AND ((start_time >= '13:00' AND start_time < '17:00') 
                     OR (end_time > '13:00' AND end_time <= '17:00'))";
            break;
        case 'evening':
            $sql .= " AND ((start_time >= '17:00' AND start_time < '22:00') 
                     OR (end_time > '17:00' AND end_time <= '22:00'))";
            break;
    }
    $sql .= ")";
    $params[] = $date;
    $types .= "s";
}

// Thực hiện query với prepared statement
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Kết quả tìm kiếm sân</title>
    <?php include 'header.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h4 class="mb-4">
                    Danh sách sân bóng 
                    <?php 
                    if (!empty($date)) {
                        echo " - Ngày " . date('d/m/Y', strtotime($date));
                    }
                    if (!empty($time_slot)) {
                        echo " - ";
                        switch($time_slot) {
                            case 'morning':
                                echo "Buổi sáng (6:00 - 11:00)";
                                break;
                            case 'afternoon':
                                echo "Buổi chiều (13:00 - 17:00)";
                                break;
                            case 'evening':
                                echo "Buổi tối (17:00 - 22:00)";
                                break;
                        }
                    }
                    if (!empty($field_type)) {
                        echo " - Sân {$field_type} người";
                    }
                    ?>
                </h4>

                <?php if(mysqli_num_rows($result) > 0): ?>
                    <div class="row">
                        <?php while($field = mysqli_fetch_assoc($result)): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img src="assets/fields/<?php echo $field['image']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo $field['name']; ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $field['name']; ?></h5>
                                        <p class="card-text">
                                            <i class="fas fa-futbol"></i> Sân <?php echo $field['field_type']; ?> người<br>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo $field['address']; ?><br>
                                            <i class="fas fa-money-bill"></i> <?php echo number_format($field['rental_price'], 0, ',', '.'); ?>đ/giờ
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?php echo $field['status'] == 'Đang trống' ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $field['status']; ?>
                                            </span>
                                            <a href="field-detail.php?id=<?php echo $field['id']; ?>" 
                                               class="btn btn-primary">Xem chi tiết</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Không tìm thấy sân bóng nào phù hợp với tiêu chí tìm kiếm.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>