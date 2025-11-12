<?php
include '../database/DBController.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// Lấy tháng và năm hiện tại
$current_month = date('m');
$current_year = date('Y');

// Thống kê doanh thu tháng hiện tại từ cả đặt sân thường và định kỳ
$monthly_revenue_query = mysqli_query($conn, "
    SELECT 
        date,
        SUM(total_bookings) as total_bookings,
        SUM(daily_revenue) as daily_revenue
    FROM (
        -- Doanh thu từ đặt sân thường
        SELECT 
            DATE(booking_date) as date,
            COUNT(*) as total_bookings,
            SUM(total_price) as daily_revenue
        FROM bookings 
        WHERE MONTH(booking_date) = '$current_month' 
        AND YEAR(booking_date) = '$current_year'
        AND status = 'Đã xác nhận'
        GROUP BY DATE(booking_date)
        
        UNION ALL
        
        -- Doanh thu từ đặt sân định kỳ
        SELECT 
            DATE(start_date) as date,
            COUNT(*) as total_bookings,
            SUM(total_price) as daily_revenue
        FROM recurring_bookings 
        WHERE MONTH(start_date) = '$current_month' 
        AND YEAR(start_date) = '$current_year'
        AND status = 'Đã xác nhận'
        GROUP BY DATE(start_date)
    ) as combined
    GROUP BY date
    ORDER BY date DESC
") or die('Query failed');

// Thống kê doanh thu tổng theo từng tháng
$total_revenue_query = mysqli_query($conn, "
    SELECT 
        year,
        month,
        SUM(total_bookings) as total_bookings,
        SUM(monthly_revenue) as monthly_revenue
    FROM (
        -- Doanh thu từ đặt sân thường
        SELECT 
            YEAR(booking_date) as year,
            MONTH(booking_date) as month,
            COUNT(*) as total_bookings,
            SUM(total_price) as monthly_revenue
        FROM bookings 
        WHERE status = 'Đã xác nhận'
        GROUP BY YEAR(booking_date), MONTH(booking_date)
        
        UNION ALL
        
        -- Doanh thu từ đặt sân định kỳ
        SELECT 
            YEAR(start_date) as year,
            MONTH(start_date) as month,
            COUNT(*) as total_bookings,
            SUM(total_price) as monthly_revenue
        FROM recurring_bookings 
        WHERE status = 'Đã xác nhận'
        GROUP BY YEAR(start_date), MONTH(start_date)
    ) as combined
    GROUP BY year, month
    ORDER BY year DESC, month DESC
") or die('Query failed');

// Tính tổng doanh thu của tháng hiện tại
$current_month_total = mysqli_query($conn, "
    SELECT SUM(total) as total
    FROM (
        -- Doanh thu từ đặt sân thường
        SELECT SUM(total_price) as total
        FROM bookings 
        WHERE MONTH(booking_date) = '$current_month' 
        AND YEAR(booking_date) = '$current_year'
        AND status = 'Đã xác nhận'
        
        UNION ALL
        
        -- Doanh thu từ đặt sân định kỳ
        SELECT SUM(total_price) as total
        FROM recurring_bookings 
        WHERE MONTH(start_date) = '$current_month' 
        AND YEAR(start_date) = '$current_year'
        AND status = 'Đã xác nhận'
    ) as combined
") or die('Query failed');
$current_month_revenue = mysqli_fetch_assoc($current_month_total)['total'] ?? 0;

// Tính tổng doanh thu từ trước đến nay
$all_time_total = mysqli_query($conn, "
    SELECT SUM(total) as total
    FROM (
        -- Tổng doanh thu từ đặt sân thường
        SELECT SUM(total_price) as total
        FROM bookings 
        WHERE status = 'Đã xác nhận'
        
        UNION ALL
        
        -- Tổng doanh thu từ đặt sân định kỳ
        SELECT SUM(total_price) as total
        FROM recurring_bookings 
        WHERE status = 'Đã xác nhận'
    ) as combined
") or die('Query failed');
$all_time_revenue = mysqli_fetch_assoc($all_time_total)['total'] ?? 0;

// Thống kê sân được thuê nhiều nhất
$popular_fields_query = mysqli_query($conn, "
    SELECT 
        field_name,
        SUM(booking_count) as booking_count
    FROM (
        -- Số lượt đặt từ đặt sân thường
        SELECT 
            f.name as field_name,
            COUNT(*) as booking_count
        FROM bookings b
        JOIN football_fields f ON b.field_id = f.id
        WHERE b.status = 'Đã xác nhận'
        GROUP BY b.field_id, f.name
        
        UNION ALL
        
        -- Số lượt đặt từ đặt sân định kỳ
        SELECT 
            f.name as field_name,
            COUNT(*) as booking_count
        FROM recurring_bookings rb
        JOIN football_fields f ON rb.field_id = f.id
        WHERE rb.status = 'Đã xác nhận'
        GROUP BY rb.field_id, f.name
    ) as combined
    GROUP BY field_name
    ORDER BY booking_count DESC
") or die('Query failed');

// Thống kê khung giờ được thuê nhiều nhất
$popular_times_query = mysqli_query($conn, "
    SELECT 
        start_time,
        SUM(time_count) as time_count
    FROM (
        -- Khung giờ từ đặt sân thường
        SELECT 
            start_time,
            COUNT(*) as time_count
        FROM bookings
        WHERE status = 'Đã xác nhận'
        GROUP BY start_time
        
        UNION ALL
        
        -- Khung giờ từ đặt sân định kỳ
        SELECT 
            start_time,
            COUNT(*) as time_count
        FROM recurring_bookings
        WHERE status = 'Đã xác nhận'
        GROUP BY start_time
    ) as combined
    GROUP BY start_time
    ORDER BY time_count DESC
") or die('Query failed');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
            <div style="background-color: #28a745" class="text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Thống kê doanh thu</h1>
            </div>

            <div class="container">
                <!-- Tổng quan doanh thu -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Doanh thu tháng <?php echo $current_month ?>/<?php echo $current_year ?></h5>
                                <h3 class="card-text"><?php echo number_format($current_month_revenue, 0, ',', '.'); ?> đ</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tổng doanh thu</h5>
                                <h3 class="card-text"><?php echo number_format($all_time_revenue, 0, ',', '.'); ?> đ</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thay thế phần biểu đồ doanh thu cũ bằng layout mới -->
                <div class="row mb-4">
                    <!-- Biểu đồ doanh thu tháng hiện tại -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Biểu đồ doanh thu tháng <?php echo $current_month ?>/<?php echo $current_year ?></h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyRevenueChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ doanh thu theo tháng -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Biểu đồ doanh thu theo tháng</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="yearlyRevenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ thống kê sân và giờ -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Sân được thuê nhiều nhất</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="popularFieldsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Khung giờ được thuê nhiều nhất</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="popularTimesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảng chi tiết -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Chi tiết doanh thu</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tháng/Năm</th>
                                                <th>Số đơn</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            mysqli_data_seek($total_revenue_query, 0);
                                            while ($row = mysqli_fetch_assoc($total_revenue_query)) {
                                                echo "<tr>";
                                                echo "<td>" . $row['month'] . "/" . $row['year'] . "</td>";
                                                echo "<td>" . $row['total_bookings'] . "</td>";
                                                echo "<td>" . number_format($row['monthly_revenue'], 0, ',', '.') . " đ</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dữ liệu cho biểu đồ doanh thu tháng hiện tại
        const monthlyData = {
            labels: [
                <?php
                mysqli_data_seek($monthly_revenue_query, 0);
                while ($row = mysqli_fetch_assoc($monthly_revenue_query)) {
                    echo "'" . date('d/m', strtotime($row['date'])) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: [
                    <?php
                    mysqli_data_seek($monthly_revenue_query, 0);
                    while ($row = mysqli_fetch_assoc($monthly_revenue_query)) {
                        echo $row['daily_revenue'] . ",";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgb(40, 167, 69)',
                borderWidth: 1
            }]
        };

        // Dữ liệu cho biểu đồ doanh thu theo tháng
        const yearlyData = {
            labels: [
                <?php
                mysqli_data_seek($total_revenue_query, 0);
                while ($row = mysqli_fetch_assoc($total_revenue_query)) {
                    echo "'" . $row['month'] . "/" . $row['year'] . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: [
                    <?php
                    mysqli_data_seek($total_revenue_query, 0);
                    while ($row = mysqli_fetch_assoc($total_revenue_query)) {
                        echo $row['monthly_revenue'] . ",";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderColor: 'rgb(0, 123, 255)',
                borderWidth: 1
            }]
        };

        // Cấu hình chung cho biểu đồ
        const options = {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' đ';
                        }
                    }
                }
            }
        };

        // Khởi tạo biểu đồ
        new Chart(
            document.getElementById('monthlyRevenueChart'),
            {
                type: 'bar',
                data: monthlyData,
                options: options
            }
        );

        new Chart(
            document.getElementById('yearlyRevenueChart'),
            {
                type: 'line',
                data: yearlyData,
                options: options
            }
        );

        // Dữ liệu cho biểu đồ sân phổ biến
        const fieldData = {
            labels: [
                <?php
                mysqli_data_seek($popular_fields_query, 0);
                while ($row = mysqli_fetch_assoc($popular_fields_query)) {
                    echo "'" . $row['field_name'] . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Số lần được thuê',
                data: [
                    <?php
                    mysqli_data_seek($popular_fields_query, 0);
                    while ($row = mysqli_fetch_assoc($popular_fields_query)) {
                        echo $row['booking_count'] . ",";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(255, 205, 86, 0.2)',
                    'rgba(255, 231, 166, 0.2)',
                ],
                borderColor: [
                    'rgb(255, 193, 7)',
                    'rgb(255, 159, 64)',
                    'rgb(255, 205, 86)',
                    'rgb(255, 231, 166)',
                ],
                borderWidth: 1
            }]
        };

        // Dữ liệu cho biểu đồ giờ phổ biến
        const timeData = {
            labels: [
                <?php
                mysqli_data_seek($popular_times_query, 0);
                while ($row = mysqli_fetch_assoc($popular_times_query)) {
                    echo "'" . $row['start_time'] . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Số lần được đặt',
                data: [
                    <?php
                    mysqli_data_seek($popular_times_query, 0);
                    while ($row = mysqli_fetch_assoc($popular_times_query)) {
                        echo $row['time_count'] . ",";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                borderColor: 'rgb(23, 162, 184)',
                borderWidth: 1
            }]
        };

        // Cấu hình cho biểu đồ sân
        const fieldOptions = {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Thống kê sân được thuê nhiều nhất'
                }
            }
        };

        // Cấu hình cho biểu đồ giờ
        const timeOptions = {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Thống kê khung giờ được thuê nhiều nhất'
                }
            }
        };

        // Khởi tạo biểu đồ sân phổ biến
        new Chart(
            document.getElementById('popularFieldsChart'),
            {
                type: 'pie',
                data: fieldData,
                options: fieldOptions
            }
        );

        // Khởi tạo biểu đồ giờ phổ biến
        new Chart(
            document.getElementById('popularTimesChart'),
            {
                type: 'bar',
                data: timeData,
                options: timeOptions
            }
        );
    </script>
</body>
</html>