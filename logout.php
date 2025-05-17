<?php
session_start();
session_unset(); 
session_destroy();

// Redirect to login or home page
header("Location: index.php"); 
exit();
?>
