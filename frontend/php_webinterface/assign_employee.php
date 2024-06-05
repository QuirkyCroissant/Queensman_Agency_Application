<?php
    session_start();

    // Login required
    if (!isset($_SESSION['UserData']['user'])) {
        header("location:login.php");
        exit;
    }

    // Include DatabaseHelper.php file
    require_once('DatabaseHelper.php');

    // Instantiate DatabaseHelper class
    $database = new DatabaseHelper();

    if (isset($_POST['e_id']) && isset($_POST['branch']) && isset($_POST['since'])) {
        $e_id = $_POST['e_id'];
        $branch = $_POST['branch'];
        $since = $_POST['since'];

        $success = $database->assignEmployeeToBranch($e_id, $branch, $since);

        if ($success) {
            $_SESSION['message'] = "Employee assigned successfully.";
        } else {
            $_SESSION['message'] = "Error assigning employee.";
        }
    }

    header("Location: showHQ.php");
    exit;
?>
