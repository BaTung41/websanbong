<?php
session_start();
include './database/DBController.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$select_admin = mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'");
    $admin = mysqli_fetch_assoc($select_admin);
    $receiver_id = $admin['user_id'];

$query = "SELECT * FROM message 
          WHERE (sender_id = '$user_id' AND receiver_id = '$receiver_id') 
             OR (sender_id = '$receiver_id' AND receiver_id = '$user_id') 
          ORDER BY created_at ASC";

$result = mysqli_query($conn, $query);
$messages = [];

while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

// Trả về tin nhắn dưới dạng JSON
echo json_encode(['status' => 'success', 'messages' => $messages, 'current_user' => $user_id]);
