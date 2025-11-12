<?php
ob_start();
session_start();

$user_id = @$_SESSION['user_id'];
if (!isset($user_id)) {
   header('location:./login.php');
   exit();
}
// include header.php file
include ('header.php');
include './database/DBController.php';

?>

<?php

    /*  include cart items if it is not empty */
       include ('Template/_orders.php');
    /*  include cart items if it is not empty */
         include('./message.php');


?>

<?php
// include footer.php file
include ('footer.php');
?>


