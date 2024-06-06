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
        $till = $_POST['till'];

        $success = $database->assignEmployeeToBranch($e_id, $branch, $since, $till);

        if ($success) {
            $_SESSION['message'] = "Employee assigned successfully.";
        } else {
            $_SESSION['message'] = "Error assigning employee. Make sure the entrance date is later than the previous assignments.";
        }
    }

    header("Location: showHQ.php");
    exit;
?>
