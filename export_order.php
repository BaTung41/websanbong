<?php
include './database/DBController.php';
require('fpdf186/fpdf.php');
session_start();

// Kiểm tra nếu người dùng đã đăng nhập và có tham số order_id
if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: order.php'); // Chuyển hướng nếu không đủ thông tin
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'];

// Lấy thông tin đơn hàng từ bảng orders
$query = "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.id = $order_id AND o.user_id = $user_id";
$result = mysqli_query($conn, $query) or die('Query failed');

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='order.php';</script>";
    exit();
}

$order = mysqli_fetch_assoc($result);

// Lấy danh sách sản phẩm trong đơn hàng
$orderDetailsQuery = "SELECT od.*, p.item_name, p.item_price FROM order_details od JOIN products p ON od.item_id = p.item_id WHERE od.order_id = $order_id";
$orderDetailsResult = mysqli_query($conn, $orderDetailsQuery) or die('Query failed');

// Tạo file PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Tiêu đề hóa đơn
$pdf->Cell(0, 10, 'HOA DON MUA HANG', 0, 1, 'C');
$pdf->Ln(10);

// Thông tin khách hàng
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Ten khach hang: ' . $order['username'], 0, 1);
$pdf->Cell(0, 10, 'Email: ' . $order['email'], 0, 1);
$pdf->Cell(0, 10, 'Dia chi: ' . $order['address'], 0, 1);
$pdf->Cell(0, 10, 'Phuong thuc thanh toan: ' . $order['method'], 0, 1);
$pdf->Cell(0, 10, 'Ghi chu: ' . $order['note'], 0, 1);
$pdf->Ln(5);

// Danh sách sản phẩm
$pdf->Cell(0, 10, 'Danh sach san pham:', 0, 1);
$pdf->Cell(100, 10, 'San pham', 1);
$pdf->Cell(30, 10, 'So luong', 1);
$pdf->Cell(30, 10, 'Gia', 1);
$pdf->Cell(30, 10, 'Thanh tien', 1);
$pdf->Ln(10);

$totalPrice = 0;

// Hiển thị sản phẩm trong đơn hàng
while ($item = mysqli_fetch_assoc($orderDetailsResult)) {
    $itemTotal = $item['quantity'] * $item['item_price'];
    $totalPrice += $itemTotal;

    $pdf->Cell(100, 10, $item['item_name'], 1);
    $pdf->Cell(30, 10, $item['quantity'], 1);
    $pdf->Cell(30, 10, number_format($item['item_price'], 0, ',', '.'), 1);
    $pdf->Cell(30, 10, number_format($itemTotal, 0, ',', '.'), 1);
    $pdf->Ln(10);
}

// Tổng tiền
$pdf->Ln(5);
$pdf->Cell(0, 10, 'Tong tien: ' . number_format($totalPrice, 0, ',', '.') . ' VND', 0, 1);
$pdf->Ln(10);

// Thông tin thời gian và trạng thái
$pdf->Cell(0, 10, 'Ngay dat: ' . date('d/m/Y H:i:s', strtotime($order['created_at'])), 0, 1);
$pdf->Cell(0, 10, 'Trang thai: ' . ($order['status'] == 0 ? 'Chờ xác nhận' : ($order['status'] == 1 ? 'Đã xác nhận' : ($order['status'] == 2 ? 'Đang vận chuyển' : 'Hoàn thành'))), 0, 1);

// Xuất file PDF
$pdf->Output('D', 'Hoa_Don_' . $order['id'] . '.pdf');
?>
