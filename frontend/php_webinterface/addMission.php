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


    $targets_array = $database->selectAllSubjects();
    $partners_array = $database->selectAllPartners();


    function cancel(){
        window.history.go(-1);
    }

    if(isset($_POST['bt_addMission_submit'])){
        
        //Grab variables from POST request
        $a_id = '';
        if(isset($_POST['m_a_id'])){
            $a_id = $_POST['m_a_id'];
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

        if($a_id != '' && $m_code != '' && $desc != '' && $m_date != '' && $m_going != '' && $m_subjects != '' && $m_partners != ''){
            
            // Insert method
            $success = $database->insertMission($a_id, $m_code, $desc, $m_date, $m_going, $m_subjects, $m_partners);
            
            // Check result
            if ($success){
                echo $success;
                $success = $database->insertTakesOn($a_id);
                if($success){
                    header('location:index.php?Agent='.$a_id.'&bt_Missions=Should+you+choose+to+accept+it');
                }
            }
            else{
                echo "Error can't insert Missionlog '{$m_code} {$desc} {$m_date} {$m_going} {$m_subjects} {$m_partners}'!";
            }
        }
    }

?>

<html>
    <head>
        <title>Queensman: Adding Mission</title>
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

        <div class="addMissionContainer">
            <form method="post">
				
				<!-- <div>
					<img class="" src="Queensman_logo_green.png">
                </div> -->
				<table>
                    <!-- m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME -->
                    <tr>
						<th colspan="4"> <label><h2>Adding Mission: </h2></label> </th>
                        <td> &nbsp;</td>
					</tr>
					<tr>
						<th> <label><h3>Agent Number: </h3></label> </th>
                        <td> <input type="text" style="pointer-events: none" value="<?php echo $_GET['bt_addMission']; ?>" name="m_a_id" > </td>
                        <th> <label><h3>Mission CODENAME: </h3></label> </th>
						<td> <input type="text" placeholder="Codename" name="m_code" maxlength="50" required> </td>
                    </tr>
                    <tr>
						<th> <label><h3>DESCRIPTION: </h3></label> </th> 
						<td colspan="3"><textarea name="m_desc" rows="10" cols="50" placeholder="Description.">  </textarea>  </td>
					</tr>
                    <tr>
						<th> <label><h3>Mission Date: </h3></label> </th>
						<td> <input type="date" name="m_date" required> </td>
                        <th> <label><h3>ONGOING: </h3></label> </th>
						<td> <input type="number" placeholder="1" name="m_going"  min="0" max="1" required> </td>
					</tr>
                    <tr>
						<th> <label><h3>Subject: </h3></label> </th>
						<td>    <select name="m_subjects" required>
                                    <?php foreach($targets_array as $target) : ?>
                                        <option value="<?php echo $target['S_ID']; ?>"><?php echo $target['S_ID']." - ".$target['FIRST_NAME']." ".$target['LAST_NAME']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                        </td>
                        <th> <label><h3>Partner: </h3></label> </th>
						<td>    <select name="m_partners">
                                        <option value="null">-</option>
                                    <?php foreach($partners_array as $partner) : ?>
                                        <option value="<?php echo $partner['P_ID']; ?>"><?php echo $partner['NAME']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                        </td>
					</tr>
					<tr style="text-align: center">
                        <td> <button name="bt_addMission_submit" >Add Mission</button> </td>
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