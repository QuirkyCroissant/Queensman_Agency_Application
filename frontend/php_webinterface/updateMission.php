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
    $mission_data = $database->selectSpecificMission($_GET['bt_updateMission']);
    
    $mission_data_m_id='';
    $mission_data_code='';
    $mission_data_desc='';
    $mission_data_date='';
    $mission_data_ongoing='';
    $mission_data_subject='';
    $mission_data_partner='';

    // There is only one row but with this loop we can assign the variables
    foreach ($mission_data as $m) :
        $mission_data_m_id= $m['M_ID'];
        $mission_data_code = $m['CODENAME'];
        $mission_data_desc = $m['DESCRIPTION'];
        $mission_data_date = $m['M_DATE'];
        $mission_data_ongoing = $m['ONGOING'];
        $mission_data_subject = $m['FK_S_ID'];
        $mission_data_partner = $m['FK_P_ID'];
    endforeach;
    
    $targets_array = $database->selectAllSubjects();
    $partners_array = $database->selectAllPartners();
    
    function cancel(){
        window.history.go(-1);
    }

    if(isset($_POST['bt_updateMission_submit'])){
        
        //Grab variables from POST request
        $m_id = '';
        if(isset($_POST['m_id'])){
            $m_id = $_POST['m_id'];
        }
        
        $m_code = '';
        if(isset($_POST['m_code'])){
            $m_code = $_POST['m_code'];
        }
        
        $desc = '';
        if(isset($_POST['m_desc'])){
            $desc = $_POST['m_desc'];
        }

        $m_date = '';
        if(isset($_POST['m_date'])){
            $m_date = $_POST['m_date'];
        }

        $m_going = '';
        if(isset($_POST['m_going'])){
            $m_going = $_POST['m_going'];
        }

        $m_subjects = '';
        if(isset($_POST['m_subjects'])){
            $m_subjects = $_POST['m_subjects'];
        }

        $m_partners = '';
        if(isset($_POST['m_partners'])){
            $m_partners = $_POST['m_partners'];
        }

        //echo $a_id.", ".$m_code.", ".$desc.", ".$m_date.", ".$m_going.", ".$m_subjects.", ".$m_partners;

        if($m_id != '' && $m_code != '' && $desc != '' && $m_date != '' && $m_going != '' && $m_subjects != '' && $m_partners != ''){

            // Insert method
            $success = $database->updateSpecificMission($m_id, $m_code, $desc, $m_date, $m_going, $m_subjects, $m_partners);
            
            // Check result
            if ($success){ 
                header('location:index.php'); 
            }
            else{
                echo "Error can't update Missionlog '{$m_code} {$desc} {$m_date} {$m_going} {$m_subjects} {$m_partners}'!";
            }
        }
    }

?>

<html>
    <head>
        <title>Queensman: Altering Mission</title>
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

        <div class="updateMissionContainer">
            <form method="post">
				
				<!-- <div>
					<img class="" src="Queensman_logo_green.png">
                </div> -->
				<table>
                    <!-- m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME -->
                    <tr>
						<th colspan="4"> <label><h2>Altering Mission: </h2></label> </th>
                        <td> &nbsp;</td>
					</tr>
					<tr>
						<th> <label><h3>Mission Number: </h3></label> </th>
                        <td> <input type="text" style="pointer-events: none" value="<?php echo $_GET['bt_updateMission']; ?>" name="m_id" > </td>
                        <th> <label><h3>Mission CODENAME: </h3></label> </th>
						<td> <input type="text" placeholder="Codename" value="<?php echo $mission_data_code; ?>" name="m_code" maxlength="50" required> </td>
                    </tr>
                    <tr>
						<th> <label><h3>DESCRIPTION: </h3></label> </th> 
						<td colspan="3"><textarea name="m_desc" rows="10" cols="50" placeholder="Description."><?php echo $mission_data_desc; ?></textarea>  </td>
					</tr>
                    <tr>
						<th> <label><h3>Mission Date: </h3></label> </th>
						<td> <input type="date" name="m_date" required> <br />current: <?php echo $mission_data_date; ?></td>
                        <th> <label><h3>ONGOING: </h3></label> </th>
						<td> <input type="number" placeholder="1" name="m_going"  min="0" max="1" value="<?php echo $mission_data_ongoing; ?>" required> </td>
					</tr>
                    <tr>
						<th> <label><h3>Subject: </h3></label> </th>
						<td>    <select name="m_subjects" value="<?php echo $mission_data_subject; ?>" required>
                                    <?php foreach($targets_array as $target) : 
                                        if($mission_data_subject == $target['S_ID'])
                                            echo '<option selected="selected" value="'.$target['S_ID'].'">'.$target['S_ID'].' - '.$target['FIRST_NAME'].' '.$target['LAST_NAME'].'</option>';
                                        else
                                        echo '<option value="'.$target['S_ID'].'">'.$target['S_ID'].' - '.$target['FIRST_NAME'].' '.$target['LAST_NAME'].'</option>';
                                    endforeach; ?>
                                </select>
                        </td>
                        <th> <label><h3>Partner: </h3></label> </th>
						<td>    <select name="m_partners" value="<?php echo $mission_data_partner; ?>">
                                        <option value="null">-</option>
                                    <?php foreach($partners_array as $partner) :
                                        if($mission_data_partner == $partner['P_ID'])
                                            echo '<option selected="selected" value="'.$partner['P_ID'].'">'.$partner['NAME'].'</option>';
                                        else
                                            echo '<option value="'.$partner['P_ID'].'">'.$partner['NAME'].'</option>';
                                    endforeach; ?>
                                </select>
                        </td>
					</tr>
					<tr style="text-align: center">
                        <td> <button name="bt_updateMission_submit" >Save Changes</button> </td>
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