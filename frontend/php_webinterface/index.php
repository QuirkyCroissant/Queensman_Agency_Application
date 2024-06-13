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

    //Fetch data from database
    $agents_array = $database->selectAllAgents();


    if(isset($_POST['bt_delTool'])){

        $database->deleteTool($_POST['bt_delTool']);
        header("Refresh:0");
    }

    if(isset($_POST['bt_delMission'])){

        echo $_POST['bt_delMission'] ;
        $database->deleteMission($_POST['bt_delMission']);
        header("Refresh:0");
    }

    if (isset($_GET['bt_successAgents'])) {
        $successful_agents = $database->getSuccessfulAgentsReport();
    }

    if (isset($_POST['assignAgentsToMission'])) {
        $m_id = $_POST['Mission'];
        $agent_ids = isset($_POST['agents']) ? $_POST['agents'] : [];
        $database->assignAgentsToMission($m_id, $agent_ids);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $assigned_agent_ids = [];
    if (isset($_POST['Mission']) && isset($_POST['bt_selectMission'])) 
    {
        $m_id = $_POST['Mission'];
        $assigned_agents = $database->selectAgentsAssignedToMission($m_id);
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $assigned_agent_ids = array_column($assigned_agents, 'agent_id');
        } else {
            $assigned_agent_ids = array_column($assigned_agents, 'A_ID');
        }
    }

    if (isset($_POST['bt_migrateToMongo'])) {
        $_SESSION['use_mongodb'] = true;
        // Execute the Python script for migration
        $output = shell_exec('python3 /var/www/html/scripts/mongo_insert.py');

        echo "<pre>$output</pre>";
    }

    if (isset($_POST['bt_fillRelDatabase'])) {
        // Execute the Python script for migration
        $database->RefillDatabase();
    }
?>


<html>
    <head>
        <title>Queensman: Agents</title>
        <link rel="stylesheet" href="design.css">
        <link rel="icon" type="image/x-icon" href="favicon.ico">

    </head>

    <body>
        <div class="masterdiv">
        <div class="header_div">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 33.33%;">
                        <div style="display: flex; align-items: center;">
                            Welcome <?php echo $_SESSION['Username']; ?>
                            <?php if (!isset($_SESSION['use_mongodb'])) { ?>
                                <form action="" method="post" style="margin: 0; padding: 0; display: inline;">
                                    <button name="bt_fillRelDatabase" type="submit" style="margin-left: 10px;">ReFill Database</button>
                                </form>
                            <?php } ?>
                            <form action="" method="post" style="margin: 0; padding: 0; display: inline;">
                                <button name="bt_migrateToMongo" type="submit" style="margin-left: 10px;">Migrate to MongoDB</button>
                            </form>
                        </div>
                    </td>
                    <td style="width: 33.33%;"> <img class="header_img" src="Queensman_logo_green.png"> </td>
                    <td style="width: 33.33%;"> <a href="logout.php" class="logout">Logout</a></td>
                </tr>
            </table>
        </div>


            <form action="" method="get" name="agent_form">
                <div class="agent_table" >
                    <table class="a_t" cellspacing="0" cellpadding="4">
                        <tr>
                            <!-- e.E_ID, e.FIRST_NAME, e.LAST_NAME, a.A_ID, a.CAPABILITY_LEVEL, a.A_ROLE -->
                            <th>A_ID</th>
                            <th>FIRST NAME</th>
                            <th>LAST NAME</th>
                            <th>E_ID</th>
                            <th>LEVEL</th>
                            <th>A_ROLE</th>
                            <th></th>
                        </tr>
                    <?php foreach ($agents_array as $agent) : ?>
                        <tr>
                            <td><?php echo $agent['A_ID']; ?>  </td>
                            <td><?php echo $agent['FIRST_NAME']; ?>  </td>
                            <td><?php echo $agent['LAST_NAME']; ?>  </td>
                            <td><?php echo $agent['E_ID']; ?>  </td>
                            <td><?php echo $agent['CAPABILITY_LEVEL']; ?>  </td>
                            <td><?php echo $agent['A_ROLE']; ?>  </td>
                            <td>
                                <input name="Agent" class="w3-radio" type="radio" value="<?php echo $agent['A_ID']; ?>">
                            </td>
                            <td class="desk-duty-cell">
                                <form action="showHQ.php" method="get">
                                    <button name="bt_changeEmployeePOV" value="<?php echo $agent['E_ID']; ?>">Desk Duty</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </table>
                </div>


                

                <div class="option_div">
                    <table class="option_table">
                        <tr style="text-align: center">
                            <td >GADGETS: <input name="bt_Gadgets" type="submit" value="Q's List"></td>
                        </tr>
                        <tr>
                            <td >MISSIONS: <input name="bt_Missions" type="submit" value="Show Agent Mission"></td>
                        </tr>
                        <tr>
                            <td > ALL MISSIONS: <input name="bt_allMissions" type="submit" value="Show all missions"></td>
                        </tr>
                        <tr>
                            <td > Show Best Agents: <input name="bt_successAgents" type="submit" value="Show Successful Agents"></td>
                        </tr>
                        <tr>
                            <td> &nbsp; <!-- COMING SOON... ADDING AGENTS / UPDATING AGENT --> </td>
                        </tr>
                        
                    </table>
                </div>
            </form>

            <div class="res_div">
                <form action="" method="post" name="mission_form">
                    <table class="res_table" cellspacing="0" cellpadding="4" >
                        <?php 
                
                            if(isset($_GET['Agent']) && isset($_GET['bt_Gadgets'])){

                                $a_id = $_GET['Agent'];
                                $gadget_list = $database->selectIndivGadgets($a_id);
                        ?>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Agent #<?php echo $a_id ?></th>
                                </tr>
                                <tr>
                                    <!-- t.T_ID, t.DESCRIPTION, t.AMOUNT -->
                                    <th>TOOL #</th>
                                    <th>DESCRIPTION</th>
                                    <th>AMOUNT</th>
                                    <th> </th>
                                </tr>
                                <?php foreach ($gadget_list as $gadget) : ?>
                                    <tr>
                                        <td><?php echo $gadget['T_ID']; ?>  </td>
                                        <td><?php echo $gadget['DESCRIPTION']; ?>  </td>
                                        <td><?php echo $gadget['AMOUNT']; ?>  </td>
                                        <form action="updateTool.php" method="get">
                                            <td><button name="bt_updateTool" value="<?php echo $gadget['T_ID']; ?>">UPDATE</button></td>
                                        </form>
                                        <form action="" method="post" name="del_tool_form">
                                            <td><button name="bt_delTool" value="<?php echo $gadget['T_ID']; ?>">DELETE</button></td>
                                        </form>    
                                    </tr>
                                <?php endforeach; ?>

                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td style="border-top: 1px solid lime; font-weight: bold">
                                                <?php 
                                                    echo $database->countAgentTools($a_id);
                                                ?>
                                            </td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            
                                        </tr>

                                        <tr>
                                            <td>&nbsp;</td>
                                            <form action="addTool.php" method="get">
                                                <td> <button name="bt_addTool" value="<?php echo $_GET['Agent']; ?>">Add Tool</button> </td>
                                            </form>
                                        </tr>
                                    
                    <?php   }  
                    
                            if(isset($_GET['Agent']) && isset($_GET['bt_Missions'])){

                                $a_id = $_GET['Agent'];
                                $missions_list = $database->selectIndivMissions($a_id);
                                
                        ?>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Agent #<?php echo $a_id ?></th>
                                </tr>
                                <tr>
                                    <!-- m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME -->
                                    <th>MISSION #</th>
                                    <th>CODENAME</th>
                                    <!-- <th>DESCRIPTION</th> -->
                                    <th>MISSION DATE</th>
                                    <th>ONGOING</th>
                                    <th>STATUS</th>
                                    <th>TARGET NAME</th>
                                    <th>COI</th>
                                    <th>EXTERN PARTNER</th>
                                    <th> </th>
                                </tr>
                                <?php foreach ($missions_list as $mission) : ?>
                                    <tr>
                                        <td><?php echo $mission['M_ID']; ?>  </td>
                                        <td><?php echo $mission['CODENAME']; ?>  </td>
                                        <!-- <td><?php //echo $mission['DESCRIPTION']; ?>  </td> -->
                                        <td><?php echo $mission['M_DATE']; ?>  </td>
                                        <td><?php echo $mission['ONGOING']; ?>  </td>
                                        <td><?php echo $mission['STATUS']; ?>  </td>
                                        <td><?php echo $mission['FIRST_NAME']." ".$mission['LAST_NAME']; ?>  </td>
                                        <td><?php echo $mission['COI']; ?>  </td>
                                        <td><?php if(!isset($mission['NAME'])) { echo "N/A"; } else { echo $mission['NAME']; }?>  </td>
                                        <form action="updateMission.php" method="get">
                                            <td><button name="bt_updateMission" value="<?php echo $mission['M_ID']; ?>">UPDATE</button></td>
                                        </form>
                                        <form action="" method="post" name="del_mission_form">
                                            <td><button name="bt_delMission" value="<?php echo $mission['M_ID']; ?>">DELETE</button></td>
                                        </form>    
                                    </tr>
                                <?php endforeach; ?>

                                        <tr>
                                            <td>&nbsp;</td>
                                            <form action="addMission.php" method="get">
                                                <td> <button name="bt_addMission" value="<?php echo $_GET['Agent']; ?>">Add Mission</button> </td>
                                            </form>
                                        </tr>
                        

                    <?php   }  
                    
                    if(isset($_GET['bt_allMissions'])){

                        $missions_list = $database->selectAllMissions();
                        
                ?>
                        <tr>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <!-- m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME -->
                            <th>M_ID #</th>
                            <th>CODENAME</th>
                            <!-- <th>DESCRIPTION</th> -->
                            <th>MISSION DATE</th>
                            <th>ONGOING</th>
                            <th>STATUS</th>
                            <th> </th>
                        </tr>
                        <?php foreach ($missions_list as $mission) : ?>
                            <tr>
                                <?php if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) : ?>
                                    <td><?php echo $mission['mission_id']; ?></td>
                                    <td><?php echo $mission['codename']; ?></td>
                                    <!-- <td><?php //echo $mission['description']; ?></td> -->
                                    <td>
                                        <?php 
                                        if (isset($mission['date']['$date']['$numberLong'])) {
                                            $timestamp = (int) ($mission['date']['$date']['$numberLong'] / 1000); // Convert milliseconds to seconds -->
                                            echo date('Y-m-d', $timestamp);
                                        } else {
                                            echo '';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $mission['ongoing']; ?></td>
                                    <td><?php echo $mission['status']; ?></td>
                                    <td>
                                        <input name="Mission" class="w3-radio" type="radio" value="<?php echo $mission['mission_id']; ?>">
                                    </td>
                                <?php else : ?>
                                    <td><?php echo $mission['M_ID']; ?></td>
                                    <td><?php echo $mission['CODENAME']; ?></td>
                                    <!-- <td><?php //echo $mission['DESCRIPTION']; ?></td> -->
                                    <td><?php echo $mission['M_DATE']; ?></td>
                                    <td><?php echo $mission['ONGOING']; ?></td>
                                    <td><?php echo $mission['STATUS']; ?></td>
                                    <td>
                                        <input name="Mission" class="w3-radio" type="radio" value="<?php echo $mission['M_ID']; ?>">
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">
                                    <button name="bt_selectMission" type="submit" value="Show available Agents">Show available Agents</button>
                                </td>
                            </tr>
                            <?php } ?>
                            
                            <?php if (isset($_POST['Mission']) && isset($_POST['bt_selectMission'])) { ?>
                            <input type="hidden" name="Mission" value="<?php echo $_POST['Mission']; ?>">
                            <tr>
                                <th>&nbsp;</th>
                                <th>Mission #<?php echo $_POST['Mission']; ?></th>
                            </tr>
                            <tr>
                                <th>SELECT AGENTS</th>
                            </tr>
                            <?php foreach ($agents_array as $agent) : ?>
                            <tr>
                                <td>
                                    <?php if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) : ?>
                                        <input type="checkbox" name="agents[]" value="<?php echo $agent['A_ID']; ?>">
                                        <?php echo $agent['FIRST_NAME'] . ' ' . $agent['LAST_NAME']; ?>
                                    <?php else : ?>
                                        <input type="checkbox" name="agents[]" value="<?php echo $agent['A_ID']; ?>" <?php echo in_array($agent['A_ID'], $assigned_agent_ids) ? 'checked' : ''; ?>>
                                        <?php echo $agent['FIRST_NAME'] . ' ' . $agent['LAST_NAME']; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">
                                    <button name="assignAgentsToMission" type="submit">Assign Agents to Mission</button>
                                </td>
                            </tr>
                            <?php } ?>
                    
                            <?php if (isset($successful_agents)) { ?>
                                <tr>
                                    <th>Agent ID</th>
                                    <th>Last Name</th>
                                    <th>Succ. Missions</th>
                                    <th>Succ. Missions on uniq. Subj.</th>
                                </tr>
                                <?php if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) : ?>
                                    <?php foreach ($successful_agents as $agent) : ?>
                                        <tr>
                                            <td><?php echo $agent['agent_id']; ?></td>
                                            <td><?php echo $agent['last_name']; ?></td> <!-- TODO LASTNAME -->
                                            <td><?php echo $agent['successful_missions']; ?></td>
                                            <td><?php echo $agent['successful_missions_unique_subjects']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <?php foreach ($successful_agents as $agent) : ?>
                                        <tr>
                                            <td><?php echo $agent['A_ID']; ?></td>
                                            <td><?php echo $agent['LAST_NAME']; ?></td>
                                            <td><?php echo $agent['successful_missions']; ?></td>
                                            <td><?php echo $agent['successful_missions_unique_subjects']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php } ?>        
                    
                    </table>
                </form>
            </div>
        </div>
    </body>

</html>