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

if (isset($_GET['bt_transferHistory'])) {
    $e_id = $_GET['bt_changeEmployeePOV'];
    #$transfer_history = $database->selectTransferHistory($e_id); // still needs to be implemented
}

if (isset($_GET['bt_superior'])) {
    $e_id = $_GET['bt_changeEmployeePOV'];
    #$superior_info = $database->selectSuperiorInfo($e_id); // still needs to be implemented
}
?>

<html>
<head>
    <title>Queensman: HQ</title>
    <link rel="stylesheet" href="design.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        /* Scoped styles for showHQ.php */
        .showHQ .agent_table {
            width: 65%;
            float: left;
            margin-top: 50px;
            max-width: max-content;
            border-color: lime;
            border-style: groove;
            overflow: auto;
            max-height: 800px;
        }
        .showHQ .option_div, .showHQ .res_div {
            width: 30%;
            float: right;
            margin-top: 50px;
            border-color: lime;
            border-style: groove;
        }
        .showHQ .agent_table table {
            table-layout: fixed;
            width: 100%;
        }
        .showHQ .agent_table th, .showHQ .agent_table td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .showHQ .agent_table th:nth-child(1), .showHQ .agent_table td:nth-child(1) { width: 5%; }
        .showHQ .agent_table th:nth-child(2), .showHQ .agent_table td:nth-child(2) { width: 10%; }
        .showHQ .agent_table th:nth-child(3), .showHQ .agent_table td:nth-child(3) { width: 10%; }
        .showHQ .agent_table th:nth-child(4), .showHQ .agent_table td:nth-child(4) { width: 15%; }
        .showHQ .agent_table th:nth-child(5), .showHQ .agent_table td:nth-child(5) { width: 15%; }
        .showHQ .agent_table th:nth-child(6), .showHQ .agent_table td:nth-child(6) { width: 15%; }
        .showHQ .agent_table th:nth-child(7), .showHQ .agent_table td:nth-child(7) { width: 10%; }
        .showHQ .agent_table th:nth-child(8), .showHQ .agent_table td:nth-child(8) { width: 10%; }
        .showHQ .agent_table th:nth-child(9), .showHQ .agent_table td:nth-child(9) { width: 10%; }
        .showHQ .header_div {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .showHQ .header_img {
            height: 50px;
        }
        .showHQ .logout {
            margin-right: 20px;
        }
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; 
            background-color: rgb(0,0,0); 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body class="showHQ">
    <div class="masterdiv">
        <div class="header_div">
            <div>Welcome <?php echo $_SESSION['Username']; ?></div>
            <img class="header_img" src="Queensman_logo_green.png">
            <a href="logout.php" class="logout">Logout</a>
        </div>

        <form action="" method="get" name="employee_form">
            <div class="agent_table">
                <table class="a_t" cellspacing="0" cellpadding="4">
                    <tr>
                        <th>E_ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                        <th>TELEPHONE</th>
                        <th>STREET</th>
                        <th>EMAIL</th>
                        <th>POST CODE</th>
                        <th>SUPERIOR</th>
                        <th></th>
                    </tr>
                    <?php foreach ($employees_array as $employee) : ?>
                        <tr class="<?php echo ($selected_e_id == $employee['E_ID']) ? 'highlight' : ''; ?>">
                            <td><?php echo $employee['E_ID']; ?></td>
                            <td><?php echo $employee['FIRST_NAME']; ?></td>
                            <td><?php echo $employee['LAST_NAME']; ?></td>
                            <td><?php echo $employee['TELEPHONE_NUMBER']; ?></td>
                            <td><?php echo $employee['STREET']; ?></td>
                            <td><?php echo $employee['EMAIL_ADDRESS']; ?></td>
                            <td><?php echo $employee['FK_POST_CODE']; ?></td>
                            <td><?php echo $employee['SUPERIOR_FS']; ?></td>
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
                        <th>TRANSFER ID</th>
                        <th>FROM DEPARTMENT</th>
                        <th>TO DEPARTMENT</th>
                        <th>DATE</th>
                    </tr>
                    <?php foreach ($transfer_history as $transfer) : ?>
                        <tr>
                            <td><?php echo $transfer['TRANSFER_ID']; ?></td>
                            <td><?php echo $transfer['FROM_DEPARTMENT']; ?></td>
                            <td><?php echo $transfer['TO_DEPARTMENT']; ?></td>
                            <td><?php echo $transfer['DATE']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (isset($superior_info)) : ?>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Employee #<?php echo $e_id ?></th>
                    </tr>
                    <tr>
                        <th>SUPERIOR ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                        <th>POSITION</th>
                    </tr>
                    <?php foreach ($superior_info as $superior) : ?>
                        <tr>
                            <td><?php echo $superior['SUPERIOR_ID']; ?></td>
                            <td><?php echo $superior['FIRST_NAME']; ?></td>
                            <td><?php echo $superior['LAST_NAME']; ?></td>
                            <td><?php echo $superior['POSITION']; ?></td>
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
        });
    </script>
</body>
</html>
