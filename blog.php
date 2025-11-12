<?php
ob_start();
session_start();

// include header.php file
include ('header.php');
include './database/DBController.php';

?>

<?php

    /*  include cart items if it is not empty */
        include ('Template/_list_blog.php');
    /*  include cart items if it is not empty */
         include('./message.php');


?>

<?php
// include footer.php file
include ('footer.php');
?>


