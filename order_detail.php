<?php
ob_start();
session_start();

// include header.php file
include('header.php');
include './database/DBController.php';

// Kiểm tra nếu người dùng đã đăng nhập và có tham số order_id
if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: order.php'); // Chuyển hướng nếu không đủ thông tin
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$order_query = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    die('Không tìm thấy đơn hàng!');
}
$order = mysqli_fetch_assoc($order_result);

// Lấy thông tin chi tiết đơn hàng
$order_details_query = "
    SELECT od.quantity, od.price AS item_price, p.item_name, p.item_image 
    FROM order_details od 
    JOIN products p ON od.item_id = p.item_id 
    WHERE od.order_id = $order_id
";
$order_details_result = mysqli_query($conn, $order_details_query);
$order_details = mysqli_fetch_all($order_details_result, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng</title>
    <!-- Bootstrap CSS -->
</head>
<body>
    <div class="container mt-5">
        <h3 class="text-center mb-4">Chi tiết đơn hàng: #<?php echo $order['id']; ?></h3>
        <div class="mb-4">
            <p><strong>Tên khách hàng:</strong> <?php echo $order['name']; ?></p>
            <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
            <p><strong>Địa chỉ:</strong> <?php echo $order['address']; ?></p>
            <p><strong>Phương thức thanh toán:</strong> <?php echo $order['method']; ?></p>
            <p><strong>Ghi chú:</strong> <?php echo $order['note']; ?></p>
            <p><strong>Trạng thái:</strong> 
                <?php 
                echo $order['status'] == 0 ? 'Chờ xác nhận' : 
                     ($order['status'] == 1 ? 'Đã xác nhận' : 
                     ($order['status'] == 2 ? 'Đang vận chuyển' :
                     ($order['status'] == 3 ? 'Hoàn thành' :  'Đã hủy'))); 
                ?>
            </p>
        </div>
        <h4 class="mb-3">Danh sách sản phẩm</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Giá</th>
                    <th>Thành tiền</th>
                    <th>Ảnh bill thanh toán</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_price = 0;
                foreach ($order_details as $item): 
                    $item_total = $item['quantity'] * $item['item_price'];
                    $total_price += $item_total;
                ?>
                <tr>
                    <td><img src="./assets/products/<?php echo $item['item_image']; ?>" alt="<?php echo $item['item_name']; ?>" style="width: 80px; height: auto;"></td>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ</td>
                    <td><?php echo number_format($item_total, 0, ',', '.'); ?> đ</td>
                </tr>
                <tr>
                    <td><img src="./assets/bill/<?php echo $item['item_image']; ?>" alt="<?php echo $item['item_name']; ?>" style="width: 80px; height: auto;"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h5 class="text-end">Tổng tiền: <?php echo number_format($total_price, 0, ',', '.'); ?> đ</h5>
    </div>
</body>
</html>

<?php
// include footer.php file
include('footer.php');
?>
