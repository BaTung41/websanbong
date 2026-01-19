
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Navbar</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>

   <div class="sidebar" style="background-color: #28a745;">
      <div class="logo">
         <img src="../assets/logo.png" alt="Logo" width="110">
      </div>
      <a style="margin-bottom: 5px;" href="admin_products.php">
         <i class="menu-icon fa fa-box"></i> Sân bóng
      </a>
      <a style="margin-bottom: 5px;" href="admin_orders.php">
         <i class="menu-icon fa fa-shopping-cart"></i> Đơn đặt sân
      </a>
      <a style="margin-bottom: 5px;" href="admin_blogs.php">
         <i class="menu-icon fa fa-newspaper"></i> Tin tức
      </a>
      <a style="margin-bottom: 5px;" href="admin_accounts.php">
         <i class="menu-icon fa fa-user"></i> Tài khoản
      </a>
      <a style="margin-bottom: 5px;" href="admin_feedback.php">
         <i class="menu-icon fa fa-comments"></i> Phản hồi / Đánh giá
      </a>
      <a style="margin-bottom: 5px;" href="admin_messages.php">
         <i class="menu-icon fa fa-comments"></i> Tin nhắn
      </a>
      <a style="margin-bottom: 5px;" href="admin_statistical.php">
         <i class="menu-icon fa fa-chart-bar"></i> Thống kê
      </a>
      <a style="margin-bottom: 5px;" href="../logout.php" class="btn btn-danger logout-btn">
         <i class="fa fa-sign-out-alt"></i> Đăng xuất
      </a>

   </div>


   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script>
      // Lấy tất cả các link trong sidebar
      const sidebarLinks = document.querySelectorAll('.sidebar a');

      // Lấy trang hiện tại từ localStorage hoặc mặc định là trang đầu tiên
      const activePage = localStorage.getItem('activePage') || sidebarLinks[0].getAttribute('href');

      // Gán class 'active' cho link tương ứng với trang hiện tại
      sidebarLinks.forEach(link => {
         if (link.getAttribute('href') === activePage) {
            link.classList.add('active');
         }
      });

      // Thêm sự kiện click cho từng link
      sidebarLinks.forEach(link => {
         link.addEventListener('click', function(e) {
            // Ngăn tải lại trang (nếu cần, tùy thuộc vào cấu trúc dự án của bạn)
            // e.preventDefault();

            // Xóa class 'active' khỏi tất cả các link
            sidebarLinks.forEach(item => item.classList.remove('active'));

            // Thêm class 'active' vào link được click
            this.classList.add('active');

            // Lưu trang hiện tại vào localStorage
            localStorage.setItem('activePage', this.getAttribute('href'));
         });
      });
   </script>
</body>

</html>