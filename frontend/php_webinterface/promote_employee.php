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


$e_id = $_GET['e_id'];

// Fetch all employees excl. caller
$employees_array = $database->selectAllEmployeesMinusE_ID($e_id);
$team_array = $database->selectTeamMembers($e_id);

// array of current team member ids
$current_team_ids = array_column($team_array, 'E_ID');

if (isset($_POST['bt_promote'])) {

    $selected_team = isset($_POST['team_members']) ? $_POST['team_members'] : [];
    
    // No team members selected, cancel the operation
    if (empty($selected_team)) {    
        $_SESSION['message'] = "Promotion canceled! No subordinates were selected.";
        header("location: showHQ.php");
        exit;
    }

    $database->updateTeamMembers($e_id, $selected_team);

    $_SESSION['message'] = "Employee #$e_id has been promoted and team updated.";
    header("location: showHQ.php");
    exit;
}
?>

<html>
<head>
    <title>Queensman: Promote Employee</title>
    <link rel="stylesheet" href="design.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .promoteContainer {
            width: 50%;
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="header_div_others">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33.33%;"> Welcome <?php echo $_SESSION['Username']; ?> </td>
                <td style="width: 33.33%;"> <img class="header_img" src="Queensman_logo_green.png"> </td>
                <td style="width: 33.33%;"> <a href="logout.php" class="logout">Logout</a> </td>
            </tr>
        </table>
    </div>

    <div class="promoteContainer">
        <form method="post">
            <table>
                <tr>
                    <th colspan="4"> <label><h2>Promote Employee #<?php echo $e_id; ?> to Team Leader</h2></label> </th>
                </tr>
                <tr>
                    <th> <label><h3>Team Members: </h3></label> </th>
                </tr>
                <tr>
                    <td colspan="4">
                        <table style="border-collapse: collapse; width: 100%;">
                            <tr>
                                <th></th>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <td> <button name="bt_promote" >Promote and Save Team</button> </td>
                            </tr>
                            <?php foreach ($employees_array as $employee) : ?>
                            <tr>
                                <td style="border: none;"><input type="checkbox" name="team_members[]" value="<?php echo $employee['E_ID']; ?>" <?php echo in_array($employee['E_ID'], $current_team_ids) ? 'checked' : ''; ?>></td>
                                <td style="border: none;"><?php echo $employee['E_ID']; ?></td>
                                <td style="border: none;"><?php echo $employee['FIRST_NAME']; ?></td>
                                <td style="border: none;"><?php echo $employee['LAST_NAME']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>
