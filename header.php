<?php
$user_id = @$_SESSION['user_id'] ?? 1;  

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vku Sport</title>

    <!-- Bootstrap CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <!-- Owl-carousel CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha256-UhQQ4fxEeABh4JrcmAJ1+16id/1dnlOEVCFOxDef9Lw=" crossorigin="anonymous" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css"
        integrity="sha256-kksNxjDRxd/5+jGurZUJd1sdR2v+ClrCl3svESBaJqw=" crossorigin="anonymous" />

    <!-- font awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css"
        integrity="sha256-h20CPZ0QyXlBuAw7A+KluUYx/3pK+c7lYEpqLTlxjYQ=" crossorigin="anonymous" />

    <!-- Custom CSS file -->
    <link rel="stylesheet" href="style.css">
    

    <?php
    // require functions.php file
    require('functions.php');
    ?>

    <style>
        .nav-link {
            color: white !important;
        }

        .search-product {
            width: 300px !important;
            margin-right: 50px;
        }

        .user-dropdown {
            cursor: pointer;
        }

        #userDropdown {
            padding-bottom: 5px;
            ;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #userDropdown p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }

        #userDropdown button {
            margin-top: 10px;
        }
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dropdown-item:focus, .dropdown-item:hover {
            background-color: #007bff;
            color: white;
        }
        .search-btn {
            position: absolute;
            right: 49px;
            border-radius: 0;
            height: -webkit-fill-available;
            background-color: #29312a !important;
            color: white !important;
            border: none !important;
        }
        .nav-link:hover {
            color: #000 !important;
        }
        .filter-buttons .dropdown-menu {
            min-width: 300px;
            left: -133px !important;
            display: none;
        }

        .filter-buttons .dropdown-menu.show {
            display: block;
        }

        .filter-buttons .btn {
            border: 1px solid #ddd;
        }

        .filter-buttons .dropdown-toggle::after {
            margin-left: 0.5em;
        }

        .filter-buttons .form-group {
            margin-bottom: 1rem;
        }

        .filter-buttons label {
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }

        .filter-buttons .form-control {
            font-size: 14px;
        }

        .filter-buttons .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }

        .filter-buttons .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .filter-buttons .dropdown {
            position: relative;
        }

        .filter-buttons .dropdown-menu {
            position: absolute;
            z-index: 1000;
        }
    </style>

</head>

<body>

    <!-- start #header -->
    <header id="header">
        <?php

        global $message;

        if (isset($message) && is_array($message)) { // hiển thị thông báo sau khi thao tác với biến message được gán giá trị
            foreach ($message as $msg) {
                echo '
       <div class=" alert alert-info alert-dismissible fade show" role="alert">
          <span style="font-size: 16px;">' . $msg . '</span>
          <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
       </div>';
            }
        }
        ?>
        <div class="strip d-flex justify-content-between px-4 py-1 bg-light">
            <p class="font-rale font-size-12 text-black-50 m-0">Vku Sport - 0941201816 - Hoàng Mai - Hà Nội - Việt Nam</p>
            <?php if ($user_id && $user_id != 1) { ?>
                <div class="user-dropdown" style="position: relative; display: inline-block;">
                    <i class="fas fa-user-circle" style="font-size: 30px; cursor: pointer;" id="userIcon"></i>
                    <!-- Dropdown menu -->
                    <div id="userDropdown"
                        style="display: none; position: absolute; top: 30px; right: 0; background: white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); z-index: 1000; min-width: 150px;">
                        <p class="font-rale font-size-12 text-black-50 m-0 p-3">Xin chào,
                            <?php echo $_SESSION['user_name']; ?></p>
                        <a href="./logout.php" class="btn btn-danger btn-sm w-fit">Đăng xuất</a>
                    </div>
                </div>
            <?php } else { ?>
                <div class="font-rale font-size-14">
                    <a href="./register.php" class="px-3 text-dark">Đăng ký</a>
                    <a href="./login.php" class="px-3 border-right border-left text-dark">Đăng nhập</a>
                </div>
            <?php } ?>
        </div>

        <!-- Primary Navigation -->
        <nav style=" background: #28a745;" class="navbar navbar-expand-lg navbar-dark color-header-bg">
            <a class="navbar-brand" href="./index.php">
                <img width="90" src="./assets/logo_fb.png" alt="logo" class="logo">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav m-auto font-size-20">
                    <li class="nav-item">
                        <a class="nav-link" href="./index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./my-bookings.php">Đơn đặt sân</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./personal.php">Hồ sơ cá nhân</a>
                    </li>
                </ul>
                <div class="filter-buttons ml-2">
                    <form method="get" action="./search.php" class="d-flex align-items-center">
                        <div class="filter-buttons">
                            <div class="dropdown">
                                <button type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-filter"></i> Tìm kiếm sân
                                </button>
                                <div class="dropdown-menu p-3" style="min-width: 300px;" data-bs-auto-close="false">
                                    <div class="form-group">
                                        <label>Ngày</label>
                                        <input type="date" name="date" class="form-control" 
                                               value="<?php echo $_GET['date'] ?? date('Y-m-d'); ?>"
                                               min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Thời gian</label>
                                        <select name="time_slot" class="form-control">
                                            <option value="">Tất cả các giờ</option>
                                            <option value="morning" <?php echo (@$_GET['time_slot'] == 'morning') ? 'selected' : ''; ?>>
                                                Sáng (6:00 - 11:00)
                                            </option>
                                            <option value="afternoon" <?php echo (@$_GET['time_slot'] == 'afternoon') ? 'selected' : ''; ?>>
                                                Chiều (13:00 - 17:00)
                                            </option>
                                            <option value="evening" <?php echo (@$_GET['time_slot'] == 'evening') ? 'selected' : ''; ?>>
                                                Tối (17:00 - 22:00)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Loại sân</label>
                                        <select name="field_type" class="form-control">
                                            <option value="">Tất cả loại sân</option>
                                            <option value="5" <?php echo (@$_GET['field_type'] == '5') ? 'selected' : ''; ?>>Sân 5 người</option>
                                            <option value="7" <?php echo (@$_GET['field_type'] == '7') ? 'selected' : ''; ?>>Sân 7 người</option>
                                            <option value="11" <?php echo (@$_GET['field_type'] == '11') ? 'selected' : ''; ?>>Sân 11 người</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Trạng thái</label>
                                        <select name="status" class="form-control">
                                            <option value="">Tất cả trạng thái</option>
                                            <option value="available" <?php echo (@$_GET['status'] == 'available') ? 'selected' : ''; ?>>Còn trống</option>
                                            <option value="booked" <?php echo (@$_GET['status'] == 'booked') ? 'selected' : ''; ?>>Đã đặt</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block mt-3">
                                        <i class="fas fa-search"></i> Tìm sân
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </nav>
        <!-- !Primary Navigation -->

    </header>
    <!-- !start #header -->

    <!-- start #main-site -->
    <main id="main-site">

        <script>
            document.getElementById('userIcon').addEventListener('click', function() {
                const dropdown = document.getElementById('userDropdown');
                dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
            });

            // Đóng dropdown nếu click bên ngoài
            window.addEventListener('click', function(e) {
                const dropdown = document.getElementById('userDropdown');
                const userIcon = document.getElementById('userIcon');
                if (e.target !== dropdown && e.target !== userIcon) {
                    dropdown.style.display = 'none';
                }
            });

            document.querySelector('.dropdown-menu').addEventListener('click', function(e) {
                e.stopPropagation();
            });

            $(document).ready(function() {
                $('.filter-buttons .dropdown').on('show.bs.dropdown', function () {
                    $(this).find('.dropdown-menu').first().stop(true, true).show();
                });

                $('.filter-buttons .dropdown-menu').click(function(e) {
                    e.stopPropagation();
                });
            });
        </script>