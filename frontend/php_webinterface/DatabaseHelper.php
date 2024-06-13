<?php

require 'vendor/autoload.php'; 

class DatabaseHelper
{
    const username = 'root';
    const password = 'Schikuta<3';
    const host = 'mysqldb';
    # port needed to be changed to internal listening port number to be able to 
    # communicate with docker database in internal network
    const port = '3306';
    const dbname = 'mysql_queensmandb';

    const mongoHost = 'mongodb'; 
    const mongoPort = '27017';
    const mongoDbname = 'queensmandb'; 

    protected $conn; #mysql
    protected $mongoClient;
    protected $mongoDb;

    public function __construct()
    {
            $this->conn = new mysqli(
            DatabaseHelper::host,
            DatabaseHelper::username,
            DatabaseHelper::password,
            DatabaseHelper::dbname,
            DatabaseHelper::port
        );

        if ($this->conn->connect_error) {
            die("DB error: Connection can't be established! " . $this->conn->connect_error);
        }

       $this->mongoClient = new MongoDB\Client("mongodb://" . self::mongoHost . ":" . self::mongoPort);
       $this->mongoDb = $this->mongoClient->{self::mongoDbname};

    }

    public function __destruct()
    {
        $this->conn->close();
    }

    public function selectAllAgents()
    {
        $sql = "SELECT a.A_ID, e.FIRST_NAME, e.LAST_NAME, e.E_ID, a.CAPABILITY_LEVEL, a.A_ROLE FROM AGENT a JOIN EMPLOYEE e ON a.E_ID=e.E_ID ORDER BY a.A_ID";

        $result = $this->conn->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();

        return $res;
    }

    public function selectAllEmployees(){
        $sql = "SELECT * FROM EMPLOYEE";

        $result = $this->conn->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();

        return $res;

    }
    ### usecase 2 (main) - assign employee to branch ###
    public function selectBranches() {
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $collection = $this->mongoDb->branches;
            $cursor = $collection->find(
                [],
                [
                    'projection' => [
                        'branch_id' => 1,
                        'name' => 1
                    ]
                ]
            );
            $result = iterator_to_array($cursor);
            return array_map(function($branch) {
                return [
                    'B_ID' => $branch['branch_id'],
                    'NAME' => $branch['name']
                ];
            }, $result);
        }else{
            $sql = "SELECT B_ID, NAME FROM BRANCH";
            $result = $this->conn->query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function assignEmployeeToBranch($e_id, $b_id, $since, $till) {
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $collection = $this->mongoDb->employees;

            // Convert date to MongoDB Date
            $since_date = new MongoDB\BSON\UTCDateTime(strtotime($since) * 1000);
            $till_date = $till ? new MongoDB\BSON\UTCDateTime(strtotime($till) * 1000) : null;

            // Retrieve the latest assignment for the employee
            $last_assignment = $collection->aggregate([
                [ '$match' => ['employee_id' => (int) $e_id] ],
                [ '$unwind' => '$assignments' ],
                [ '$sort' => ['assignments.since' => -1] ],
                [ '$limit' => 1 ],
                [ '$project' => ['last_since' => '$assignments.since'] ]
            ])->toArray();

            if (!empty($last_assignment)) {
                $last_since = $last_assignment[0]['last_since'];

                // New assignment date must be later than the previous assignment
                if ($since_date <= $last_since) {
                    return false;
                }

                // Update the previous assignment's "TILL" field
                $collection->updateOne(
                    [
                        'employee_id' => (int) $e_id,
                        'assignments.since' => $last_since
                    ],
                    [
                        '$set' => ['assignments.$.till' => $since_date]
                    ]
                );
            }

            // Prepare the new assignment
            $new_assignment = [
                'branch_id' => (int) $b_id,
                'since' => $since_date
            ];
            if ($till_date) {
                $new_assignment['till'] = $till_date;
            }

            // Insert the new assignment
            $result = $collection->updateOne(
                ['employee_id' => (int) $e_id],
                ['$push' => ['assignments' => $new_assignment]]
            );

            return $result->getModifiedCount() > 0;

        } else {
            // retrieves latest assignment for the employee
            $sql = "SELECT MAX(SINCE) AS last_since FROM ASSIGNED_TO WHERE ASS_E_ID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $e_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $last_assignment = $result->fetch_assoc();
            $stmt->close();

            if ($last_assignment) {
                $last_since = $last_assignment['last_since'];

                // New assignment date must be later than the previous assignment! -> return false
                if ($since <= $last_since) {
                    return false; 
                }

                // Updating the previous assignments "TILL" field
                $sql = "UPDATE ASSIGNED_TO SET TILL = ? WHERE ASS_E_ID = ? AND TILL IS NULL";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('si', $since, $e_id);
                $stmt->execute();
                $stmt->close();
            }

            // finally insert the new assignment
            // if we already get a termination date in "till" we add it to the insert
            if($till != null){
                $sql = "INSERT INTO ASSIGNED_TO (ASS_E_ID, ASS_B_ID, SINCE, TILL) VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('iiss', $e_id, $b_id, $since, $till);
            } else {
                $sql = "INSERT INTO ASSIGNED_TO (ASS_E_ID, ASS_B_ID, SINCE) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('iis', $e_id, $b_id, $since);
            }
            
            $success = $stmt->execute();
            $stmt->close();

            return $success;
        }
    }

    ######## data analytics - Employee Assignments ########
    public function getEmployeeAssignments($e_id) {
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $collection = $this->mongoDb->employees;

            $pipeline = [
                [
                    '$match' => [
                        'employee_id' => (int) $e_id
                    ]
                ],
                [
                    '$unwind' => '$assignments'
                ],
                [
                    '$lookup' => [
                        'from' => 'branches',
                        'localField' => 'assignments.branch_id',
                        'foreignField' => 'branch_id',
                        'as' => 'branch_info'
                    ]
                ],
                [
                    '$unwind' => '$branch_info'
                ],
                [
                    '$project' => [
                        'ASS_ID' => '$assignments.branch_id',
                        'NAME' => '$branch_info.name',
                        'TYPE' => '$branch_info.facility_type.type',
                        'CITY' => '$branch_info.post_code.city',
                        'SINCE' => [
                            '$dateToString' => [
                                'format' => '%Y-%m-%d',
                                'date' => '$assignments.since'
                            ]
                        ],
                        'TILL' => [
                            '$cond' => [
                                'if' => ['$eq' => ['$assignments.till', null]],
                                'then' => null,
                                'else' => [
                                    '$dateToString' => [
                                        'format' => '%Y-%m-%d',
                                        'date' => '$assignments.till'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $result = $collection->aggregate($pipeline)->toArray();

            return $result;

        } else{
            $sql = "SELECT ASS_ID, NAME, ft.`TYPE`, CITY, SINCE, TILL  
                    FROM ASSIGNED_TO 
                    LEFT JOIN BRANCH ON ASS_B_ID = B_ID 
                    LEFT JOIN FACILITY_TYPE ft ON FK_TYPE=ft.FT_ID 
                    LEFT JOIN POST_CODE pc ON FK_POST_CODE  = PC_ID 
                    WHERE ASS_E_ID = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $e_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $res = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $res;
        }
    }

    public function getSuperiorAndTeam($e_id) {
        // Query to get the superior of the employee
        $sql = "SELECT s.E_ID as SUPERIOR_ID, s.FIRST_NAME as SUPERIOR_FIRST_NAME, s.LAST_NAME as SUPERIOR_LAST_NAME
                FROM EMPLOYEE e
                JOIN EMPLOYEE s ON e.SUPERIOR_FS = s.E_ID
                WHERE e.E_ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $e_id);
        $stmt->execute();
        $superior_result = $stmt->get_result();
        $superior_info = $superior_result->fetch_assoc();
        $stmt->close();

        // Query to get the team members of the employee
        $sql = "SELECT E_ID, FIRST_NAME, LAST_NAME
                FROM EMPLOYEE
                WHERE SUPERIOR_FS = (SELECT SUPERIOR_FS FROM EMPLOYEE WHERE E_ID = ?) AND E_ID NOT LIKE ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $e_id, $e_id);
        $stmt->execute();
        $team_result = $stmt->get_result();
        $team_info = $team_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return ['superior' => $superior_info, 'team' => $team_info];
    }

    public function selectAllEmployeesMinusE_ID($e_id){
        $sql = "SELECT * FROM EMPLOYEE WHERE E_ID != ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param('i', $e_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $res;
    }

    public function selectTeamMembers($e_id) {
        // get the SUPERIOR_FS value for the given employee
        $sql = "SELECT SUPERIOR_FS FROM EMPLOYEE WHERE E_ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $e_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $superior_fs = $result->fetch_assoc()['SUPERIOR_FS'];
        $stmt->close();
    
        // Then get the team members who have the same boss
        $sql = "SELECT E_ID, FIRST_NAME, LAST_NAME
                FROM EMPLOYEE
                WHERE SUPERIOR_FS = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $superior_fs);
        $stmt->execute();
        $result = $stmt->get_result();
        $team = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    
        return $team;
    }
    

    public function updateTeamMembers($e_id, $team_members) {
        // First, reset all team members of the current employee
        $sql = "UPDATE EMPLOYEE SET SUPERIOR_FS = NULL WHERE SUPERIOR_FS = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $e_id);
        $stmt->execute();
        $stmt->close();

        // Then, update the selected team members
        if (!empty($team_members)) {
            foreach ($team_members as $member_id) {
                $sql = "UPDATE EMPLOYEE SET SUPERIOR_FS = ? WHERE E_ID = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('ii', $e_id, $member_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }


    public function selectIndivGadgets($a_id) {
        $sql = "SELECT t.T_ID, t.DESCRIPTION, t.AMOUNT FROM TOOL t JOIN AGENT a ON t.FK_A_ID=a.A_ID WHERE t.FK_A_ID = ? ORDER BY t.T_ID";

        if($stmt = $this->conn->prepare($sql)){
            $stmt->bind_param('i', $a_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $res = $result->fetch_all(MYSQLI_ASSOC);

            $stmt->close();

            return $res;
        } else{
            die("Error preparing statement: " . $this->conn->error);
        }
    }

    public function selectSpecificTool($t_id) {
        $sql = "SELECT t.FK_A_ID, t.T_ID, t.DESCRIPTION, t.AMOUNT FROM TOOL t WHERE t.T_ID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $t_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $res;
    }

    public function insertAddTool($a_id, $desc, $amount)
    {
        $sql = "INSERT INTO TOOL (FK_A_ID, DESCRIPTION, AMOUNT) VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isi', $a_id, $desc, $amount);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function updateSpecificTool($t_id, $a_id, $desc, $amount)
    {
        $sql = "UPDATE TOOL SET FK_A_ID = ?, DESCRIPTION = ?, AMOUNT = ? WHERE T_ID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isii', $a_id, $desc, $amount, $t_id);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function deleteTool($t_id)
    {
        $sql = "DELETE FROM TOOL WHERE T_ID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $t_id);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function selectIndivMissions($a_id)
    {
        $sql = "SELECT m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, m.STATUS, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME FROM MISSIONLOG m JOIN TAKES_ON t ON m.M_ID=t.FK_M_ID JOIN AGENT a ON a.A_ID=t.FK_A_ID LEFT JOIN EXTERN_PARTNER ep ON m.FK_P_ID=ep.P_ID JOIN SUBJECT s ON m.FK_S_ID=s.S_ID WHERE t.FK_A_ID = ? ORDER BY m.M_ID";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $a_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $res;
    }

    # refill database button
    public function RefillDatabase() {
        // Path to the SQL file
        $sqlPath = '/var/www/html/rebuild_data.sql';

        // Read and execute SQL file content
        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            die("Failed to read SQL file: $sqlPath");
        }

        if ($this->conn->multi_query($sql) === false) {
            die("Failed to execute SQL: " . $this->conn->error);
        }

        // Wait for all queries to complete
        do {
            if ($result = $this->conn->store_result()) {
                $result->free();
            }
        } while ($this->conn->next_result());

        // Retry logic for preventing deadlocks
        $maxRetries = 5;
        $attempts = 0;
        $success = false;
        while ($attempts < $maxRetries && !$success) {
            try {
                $output = shell_exec('python3 /var/www/html/Queensman_imse_Insert.py');
                if ($output === null) {
                    throw new Exception("Python script execution failed.");
                }
                $success = true;
            } catch (Exception $e) {
                $attempts++;
                if ($attempts >= $maxRetries) {
                    die("Max retries reached. Failed to execute Python script.");
                }
                sleep(5);
            }
        }
    }

    //################USECASE1################
    public function selectAllMissions()
    {   
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $collection = $this->mongoDb->missionlogs;
            $pipeline = [
                [
                    '$project' => [
                        'mission_id' => '$mission_id',
                        'codename' => '$codename',
                        'description' => '$description',
                        'date' => '$date',
                        'ongoing' => '$ongoing',
                        'status' => '$status'
                    ]
                ]
            ];
    
            $result = $collection->aggregate($pipeline)->toArray();
            return json_decode(json_encode($result), true); // Convert BSON to array
        }
        else {

    
        $sql = "SELECT m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, m.STATUS FROM MISSIONLOG m";

        $result = $this->conn->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();

        return $res;
        }
    }

    public function selectAgentsAssignedToMission($m_id)
    {   
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $collection = $this->mongoDb->missionlogs;
        
            $pipeline = [
                ['$match' => ['mission_id' => $m_id]],
                ['$unwind' => '$agents'],
                ['$lookup' => [
                    'from' => 'employees',
                    'localField' => 'agents.agent_id',
                    'foreignField' => 'employee_id', 
                    'as' => 'agent_info'
                ]],
                ['$unwind' => '$agent_info'],
                ['$project' => [
                    'agent_id' => '$agents.agent_id',
                    'first_name' => '$agent_info.first_name',
                    'last_name' => '$agent_info.last_name'
                ]]
            ];

            $result = $collection->aggregate($pipeline)->toArray();
            return json_decode(json_encode($result), true);
        }
        else {
        $sql = "SELECT a.A_ID, e.FIRST_NAME, e.LAST_NAME FROM AGENT a JOIN TAKES_ON t ON a.A_ID = t.FK_A_ID JOIN EMPLOYEE e ON a.E_ID = e.E_ID WHERE t.FK_M_ID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $m_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $res;
        }
    }

    public function assignAgentsToMission($m_id, $agent_ids) {
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb']) {
            $collection = $this->mongoDb->missionlogs;
            
            // Delete existing agents for the mission
            $collection->updateOne(
                ['mission_id' => $m_id],
                ['$set' => ['agents' => []]]
            );
    
            // Add the new agents
            foreach ($agent_ids as $agent_id) {
                $collection->updateOne(
                    ['mission_id' => $m_id],
                    ['$push' => ['agents' => ['agent_id' => $agent_id, 'role' => 'Assigned']]]
                );
            }
            return true;
        }   
        else {
        $sql_delete = "DELETE FROM TAKES_ON WHERE FK_M_ID = ?";
        $stmt_delete = $this->conn->prepare($sql_delete);
        $stmt_delete->bind_param('i', $m_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    
        foreach ($agent_ids as $a_id) {
            $sql_insert = "INSERT INTO TAKES_ON (FK_A_ID, FK_M_ID) VALUES (?, ?)";
            $stmt_insert = $this->conn->prepare($sql_insert);
            $stmt_insert->bind_param('ii', $a_id, $m_id);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        return true;
        }
    }
    //################USECASE1END################

    #####REPORT#####
    public function getSuccessfulAgentsReport() {
        if (isset($_SESSION['use_mongodb']) && $_SESSION['use_mongodb'])
        {   
            $collection = $this->mongoDb->missionlogs;

        $pipeline = [
            [
                '$match' => [
                    'status' => 'SUCCESSFUL',
                    'date' => [
                        '$gte' => new MongoDB\BSON\UTCDateTime(strtotime('-1 year') * 1000)
                    ]
                ]
            ],
            [
                '$unwind' => '$agents'
            ],
            [
                '$group' => [
                    '_id' => [
                        'agent_id' => '$agents.agent_id'
                    ],
                    'total_successful_missions' => ['$sum' => 1],
                    'unique_subjects' => ['$addToSet' => '$subject.subject_id']
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'employees',
                    'localField' => '_id.agent_id',
                    'foreignField' => 'roles.agent_id',
                    'as' => 'agent_info'
                ]
            ],
            [
                '$unwind' => '$agent_info'
            ],
            [
                '$group' => [
                    '_id' => [
                        'agent_id' => '$_id.agent_id',
                        'last_name' => '$agent_info.last_name'
                    ],
                    'successful_missions' => ['$first' => '$total_successful_missions'],
                    'successful_missions_unique_subjects' => ['$first' => ['$size' => '$unique_subjects']]
                ]
            ],
            [
                '$project' => [
                    'agent_id' => '$_id.agent_id',
                    'last_name' => '$_id.last_name',
                    'successful_missions' => 1,
                    'successful_missions_unique_subjects' => 1
                ]
            ],
            [
                '$sort' => [
                    'successful_missions_unique_subjects' => -1,
                    'successful_missions' => -1
                ]
            ]
        ];

        $result = $collection->aggregate($pipeline)->toArray();
        return json_decode(json_encode($result), true); // Convert BSON to array
        }
        else {
            $sql = "
                SELECT 
                    a.A_ID, 
                    e.LAST_NAME, 
                    COUNT(m.M_ID) AS successful_missions, 
                    COUNT(DISTINCT m.FK_S_ID) AS successful_missions_unique_subjects
                FROM 
                    AGENT a
                JOIN 
                    EMPLOYEE e ON a.E_ID = e.E_ID
                LEFT JOIN 
                    TAKES_ON t ON a.A_ID = t.FK_A_ID
                LEFT JOIN 
                    MISSIONLOG m ON t.FK_M_ID = m.M_ID AND m.STATUS = 'SUCCESSFUL' AND m.M_DATE >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                GROUP BY 
                    a.A_ID, e.LAST_NAME
                ORDER BY 
                    successful_missions_unique_subjects DESC, successful_missions DESC";
        
            $result = $this->conn->query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    #####REPORTEND##

    public function insertMission($m_code, $desc, $m_date, $m_going, $m_subjects, $m_partners)
    {
        $sql = "INSERT INTO MISSIONLOG (CODENAME, DESCRIPTION, M_DATE, ONGOING, FK_S_ID, FK_P_ID) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssiii', $m_code, $desc, $m_date, $m_going, $m_subjects, $m_partners);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function insertTakesOn($a_id)
    {
        $sql = "SELECT M_ID FROM MISSIONLOG ORDER BY M_ID DESC LIMIT 1";

        $result = $this->conn->query($sql);
        $res = $result->fetch_assoc();

        $sql = "INSERT INTO TAKES_ON (FK_A_ID, FK_M_ID) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $a_id, $res['M_ID']);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function selectSpecificMission($m_id)
    {
        $sql = "SELECT * FROM MISSIONLOG WHERE M_ID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $m_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $res;
    }

    public function updateSpecificMission($m_id, $m_code, $desc, $m_date, $m_going, $m_sub, $m_partner)
    {
        if ($m_partner != 'null') {
            $sql = "UPDATE MISSIONLOG SET CODENAME = ?, DESCRIPTION = ?, M_DATE = ?, ONGOING = ?, FK_S_ID = ?, FK_P_ID = ? WHERE M_ID = ?";
        } else {
            $sql = "UPDATE MISSIONLOG SET CODENAME = ?, DESCRIPTION = ?, M_DATE = ?, ONGOING = ?, FK_S_ID = ?, FK_P_ID = NULL WHERE M_ID = ?";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssiiii', $m_code, $desc, $m_date, $m_going, $m_sub, $m_partner, $m_id);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function deleteMission($m_id)
    {
        $sql = "DELETE FROM TAKES_ON WHERE FK_M_ID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $m_id);
        $success = $stmt->execute();
        $stmt->close();

        $sql = "DELETE FROM MISSIONLOG WHERE M_ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $m_id);
        $success = $stmt->execute() && $success;

        $stmt->close();

        return $success;
    }

    public function selectAllSubjects()
    {
        $sql = "SELECT S_ID, FIRST_NAME, LAST_NAME FROM SUBJECT ORDER BY S_ID";

        $result = $this->conn->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();

        return $res;
    }

    public function selectAllPartners()
    {
        $sql = "SELECT P_ID, NAME FROM EXTERN_PARTNER ORDER BY P_ID";

        $result = $this->conn->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();

        return $res;
    }

    public function countAgentTools($a_id)
    {
        $sql = 'CALL AgentToolCnt(?, @res)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $a_id);
        $stmt->execute();
        $stmt->close();

        $result = $this->conn->query('SELECT @res AS res');
        $row = $result->fetch_assoc();

        return $row['res'];
    }
}