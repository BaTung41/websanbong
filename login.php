<?php
include 'database/DBController.php';
session_start();

if (isset($_POST['submit'])) { // Xử lý khi người dùng nhấn nút "submit"
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, md5($_POST['password'])); // Mã hóa mật khẩu bằng md5

    // Truy vấn kiểm tra thông tin đăng nhập
    $query = "SELECT * FROM `users` WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query) or die('Query failed');

    // Kiểm tra kết quả truy vấn
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if ($user['status'] == 0) {
            $message[] = 'Tài khoản của bạn đã bị khóa!';
        } else {
            if ($user['role'] == 'admin') {
                // Nếu là quản trị viên
                $_SESSION['admin_name'] = $user['username'];
                $_SESSION['admin_id'] = @$user['user_id'];
                header('Location: admin/admin_products.php'); // Chuyển đến trang quản trị
                exit();
            } elseif ($user['role'] == 'user') {
                // Nếu là user
                $_SESSION['user_name'] = $user['username'];
                $_SESSION['user_id'] = $user['user_id'];
                header('Location: index.php');
                exit();
            } else {
                $message[] = 'Tài khoản của bạn không có quyền truy cập!';
            }
        }
    } else {
        $message[] = 'Tên tài khoản hoặc mật khẩu không chính xác!';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - Sân Bóng</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        .container {
            margin: auto;
            display: flex;
            justify-content: center;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('./assets/bg-login.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .form-control {
            margin-bottom: 0px !important;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            width: 400px;
            backdrop-filter: blur(10px);
        }

        .login-header {
            background: #28a745;
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .login-header h4 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .login-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            border-color: #28a745;
        }

        .btn-login {
            background: #28a745;
            border: none;
            border-radius: 25px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: #28a745;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="container">
            <?php
            if (isset($message)) {
                foreach ($message as $msg) {
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <span>' . $msg . '</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
                }
            }
            ?>
            
            <div class="login-card">
                <div class="login-header">
                    <h4><i class="fas fa-futbol me-2"></i>Đăng Nhập</h4>
                </div>
                <div class="login-body">
                    <form action="" method="post">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" name="email" class="form-control border-start-0" 
                                       placeholder="Email của bạn" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" name="password" class="form-control border-start-0" 
                                       placeholder="Mật khẩu" required>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-login btn-success w-100">
                            Đăng Nhập
                        </button>
                    </form>
                    <div class="register-link">
                        <p class="mb-0">Chưa có tài khoản? 
                            <a href="register.php">Đăng ký ngay</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>