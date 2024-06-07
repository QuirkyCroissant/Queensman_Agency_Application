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

// Fetch data from the employee database
$employees_array = $database->selectAllEmployees();

$selected_e_id = isset($_GET['bt_changeEmployeePOV']) ? $_GET['bt_changeEmployeePOV'] : null;

// fetch Employee Transfer History
if (isset($_GET['bt_transferHistory'])) {
    $e_id = $_GET['Employee'];
    $transfer_history = $database->getEmployeeAssignments($e_id); 
}

if (isset($_GET['bt_superior'])) {
    $e_id = $_GET['Employee'];
    $superior_and_team = $database->getSuperiorAndTeam($e_id);
    $superior_info = $superior_and_team['superior'];
    $team_info = $superior_and_team['team'];
}

?>

<html>
<head>
    <title>Queensman: HQ</title>
    <link rel="stylesheet" href="design.css">
    <link rel="stylesheet" href="design_showHQ.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body class="showHQ">
    <div class="masterdiv">
        <div class="header_div">
            <div>Welcome <?php echo $_SESSION['Username']; ?></div>
            <img class="header_img" src="Queensman_logo_green.png">
            <a href="logout.php" class="logout">Logout</a>
        </div>

        <?php
        if (isset($_SESSION['message'])) {
            echo "<div class='message'>" . $_SESSION['message'] . "</div>";
            unset($_SESSION['message']);
        }
        ?>

        <form action="" method="get" name="employee_form">
            <div class="agent_table">
                <table class="a_t" cellspacing="0" cellpadding="4">
                    <tr>
                        <th>E_ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                        <th>TELEPHONE</th>
                        <th>EMAIL</th>
                        <th></th>
                    </tr>
                    <?php foreach ($employees_array as $employee) : ?>
                        <tr class="<?php echo ($selected_e_id == $employee['E_ID']) ? 'highlight' : ''; ?>">
                            <td><?php echo $employee['E_ID']; ?></td>
                            <td><?php echo $employee['FIRST_NAME']; ?></td>
                            <td><?php echo $employee['LAST_NAME']; ?></td>
                            <td><?php echo $employee['TELEPHONE_NUMBER']; ?></td>
                            <td><?php echo $employee['EMAIL_ADDRESS']; ?></td>
                            <td>
                                <input name="Employee" class="w3-radio" type="radio" value="<?php echo $employee['E_ID']; ?>" <?php echo ($selected_e_id == $employee['E_ID']) ? 'checked' : ''; ?>>
                                <button type="button" class="assign-btn" data-employee-id="<?php echo $employee['E_ID']; ?>">Assign to Branch</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="option_div">
                <table class="option_table">
                    <tr style="text-align: center">
                        <td>TRANSFER HISTORY: <input name="bt_transferHistory" type="submit" value="View History"></td>
                    </tr>
                    <tr>
                        <td>SUPERIOR: <input name="bt_superior" type="submit" value="View Superior"></td>
                    </tr>
                </table>
            </div>
        </form>

        <div id="assignModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Assign Employee to Branch</h2>
                <form action="assign_employee.php" method="post">
                    <input type="hidden" name="e_id" id="modal-employee-id">
                    <label for="branch">Select Branch:</label>
                    <select name="branch" id="branch">
                        <?php
                        $branches = $database->selectBranches();
                        foreach ($branches as $branch) {
                            echo "<option value='" . $branch['B_ID'] . "'>" . $branch['NAME'] . "</option>";
                        }
                        ?>
                    </select>
                    <br><br>
                    <label for="since">Entrance Date:</label>
                    <input type="date" name="since" required>
                    <br><br>
                    <label for="till">Termination Date (optional):</label>
                    <input type="date" name="till">
                    <br><br>
                    <input type="submit" value="Assign">
                </form>
            </div>
        </div>

        <div class="res_div">
            <table class="res_table" cellspacing="0" cellpadding="4">
                <?php if (isset($transfer_history)) : ?>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Employee #<?php echo $e_id ?></th>
                    </tr>
                    <tr>
                        <th>FACILITY NAME</th>
                        <th>DEPARTMENT TYPE</th>
                        <th>CITY</th>
                        <th>SINCE</th>
                        <th>TILL</th>
                    </tr>
                    <?php foreach ($transfer_history as $transfer) : ?>
                        <tr>
                            <td><?php echo $transfer['NAME']; ?></td>
                            <td><?php echo $transfer['TYPE']; ?></td>
                            <td><?php echo $transfer['CITY']; ?></td>
                            <td><?php echo $transfer['SINCE']; ?></td>
                            <td><?php echo $transfer['TILL']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (isset($superior_info)) : ?>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Employee #<?php echo $e_id ?></th>
                        <th><button type="button" class="promote-btn" data-employee-id="<?php echo $e_id; ?>">Promote Employee</button></tr>
                    </tr>
                    <tr>
                        <th>SUPERIOR ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                    </tr>
                    <tr>
                        <td><?php echo $superior_info['SUPERIOR_ID']; ?></td>
                        <td><?php echo $superior_info['SUPERIOR_FIRST_NAME']; ?></td>
                        <td><?php echo $superior_info['SUPERIOR_LAST_NAME']; ?></td>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Team Members</th>
                        <th>&nbsp;</th>
                    </tr>
                    <tr>
                        <th>E_ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                    </tr>
                    <?php foreach ($team_info as $member) : ?>
                        <tr>
                            <td><?php echo $member['E_ID']; ?></td>
                            <td><?php echo $member['FIRST_NAME']; ?></td>
                            <td><?php echo $member['LAST_NAME']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var element = document.querySelector('.highlight');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            var modal = document.getElementById("assignModal");
            var span = document.getElementsByClassName("close")[0];

            document.querySelectorAll(".assign-btn").forEach(function(button) {
                button.onclick = function() {
                    var employeeId = this.getAttribute("data-employee-id");
                    document.getElementById("modal-employee-id").value = employeeId;
                    modal.style.display = "block";
                }
            });

            span.onclick = function() {
                modal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }

            document.querySelectorAll(".promote-btn").forEach(function(button) {
                button.onclick = function() {
                    var employeeId = this.getAttribute("data-employee-id");
                    window.location.href = "promote_employee.php?e_id=" + employeeId;
                }
            });
        });
    </script>
</body>
</html>