<?php
ob_start();

if (isset($message)) { // hiển thị thông báo sau khi thao tác với biến message được gán giá trị
    foreach ($message as $msg) {
        echo '
<div class=" alert alert-info alert-dismissible fade show" role="alert">
  <span style="font-size: 16px;">' . $msg . '</span>
  <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
</div>';
    }
}
?>
<style>
    .alert {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
    }
</style>