<?php session_start();
    session_destroy();
    //redirect to login
    header("location:login.php"); 
    exit;
?>