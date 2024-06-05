<?php session_start();

    //login required
    if(!isset($_SESSION['UserData']['user'])){
            header("location:login.php");
            exit;
    }

    // Include DatabaseHelper.php file
    require_once('DatabaseHelper.php');

    // Instantiate DatabaseHelper class
    $database = new DatabaseHelper();

    $tool_data = $database->selectSpecificTool($_GET['bt_updateTool']);
    
    $tool_data_a_id='';
    $tool_data_desc='';
    $tool_data_amount='';

    // There is only one row but with this loop we can assign the variables
    foreach ($tool_data as $tool) :
        $tool_data_a_id= $tool['FK_A_ID'];
        $tool_data_desc = $tool['DESCRIPTION'];
        $tool_data_amount = $tool['AMOUNT'];
    endforeach;
    

    function cancel(){
        window.history.go(-1);
    }

    if(isset($_POST['bt_updateTool_submit'])){
        
        //Grab variables from POST request
        $t_id = '';
        if(isset($_POST['tool_t_id'])){
            $t_id = $_POST['tool_t_id'];
        }

        $a_id = '';
        if(isset($_POST['tool_a_id'])){
            $a_id = $_POST['tool_a_id'];
        }
        
        $desc = '';
        if(isset($_POST['tool_description'])){
            $desc = $_POST['tool_description'];
        }

        $amount = '';
        if(isset($_POST['tool_amount'])){
            $amount = $_POST['tool_amount'];
        }

        if($t_id != '' && $a_id != '' && $desc != '' && $amount != ''){
            // Insert method
            $success = $database->updateSpecificTool($t_id, $a_id, $desc, $amount);
            
            // Check result
            if ($success){
                //http://wwwlab.cs.univie.ac.at/~hajekf96/index.php?Agent=59&bt_Gadgets=Q%27s+List
                header('location:index.php?Agent='.$tool_data_a_id.'&bt_Gadgets=Q%27s+List');
            }
            else{
                echo "Error can't update Tool '{$desc} {$amount}'!";
            }
        }
    }

?>

<html>
    <head>
        <title>Queensman: Updating Tools</title>
        <link rel="stylesheet" href="design.css">
        <link rel="icon" type="image/x-icon" href="favicon.ico">

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

        <div class="updateContainer">
            <form method="post">
				
				<!-- <div>
					<img class="" src="Queensman_logo_green.png">
                </div> -->
				<table>
                    <tr>
						<th> <label><h2>Altering Tool: </h2></label> </th>
                        <td> &nbsp;</td>
					</tr>
					<tr>
						<th> <label><h3>Tool ID: </h3></label> </th>
                        <td> <input type="text" style="pointer-events: none" value="<?php echo $_GET['bt_updateTool']; ?>" name="tool_t_id" > </td>
					</tr>
                    <tr>
						<th> <label><h3>Agent number: </h3></label> </th>
                        <td> <input type="number" value="<?php echo $tool_data_a_id; ?>" name="tool_a_id" min="1" max="1200"> </td>
					</tr>
					<tr>
						<th> <label><h3>Tool Description: </h3></label> </th>
						<td> <input type="text" value="<?php echo $tool_data_desc; ?>" name="tool_description" maxlength="30" required> </td>
					</tr>
                    <tr>
						<th> <label><h3>Amount: </h3></label> </th>
						<td> <input type="number" value="<?php echo $tool_data_amount; ?>" name="tool_amount" min="1" max="3" required> </td>
					</tr>
					<tr style="text-align: center">
                        <td> <button name="bt_updateTool_submit" >Save Changes</button> </td>
                        <td> 
                            <?php $referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
                                if (!empty($referer)) {
                                    echo '<p><a href="'. $referer .'">Cancel</a></p>';
                                } else {
                                    echo '<p><a href="javascript:history.go(-1)">Cancel</a></p>';
                                }
                            ?>
                        </td>
					</tr>
				</table>	
				
			</form>
        </div>

    </body>
</html>