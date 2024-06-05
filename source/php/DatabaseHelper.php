<?php
   
class DatabaseHelper
{
    // Since the connection details are constant, define them as const
    // We can refer to constants like e.g. DatabaseHelper::username
    const username = 'a12046317'; // use a + your matriculation number
    const password = 'pilguin'; // use your oracle db password
    const con_string = '//oracle19.cs.univie.ac.at:1521/orclcdb';
   
    // Since we need only one connection object, it can be stored in a member variable.
    // $conn is set in the constructor.
    protected $conn;

    // Create connection in the constructor
    public function __construct()
    {
        try {
            // Create connection with the command oci_connect(String(username), String(password), String(connection_string))
            $this->conn = oci_connect(
                DatabaseHelper::username,
                DatabaseHelper::password,
                DatabaseHelper::con_string,
                'AL32UTF8'
            );
   
            //check if the connection object is != null
            if (!$this->conn) {
                // die(String(message)): stop PHP script and output message:
                die("DB error: Connection can't be established!");
            }
   
        } catch (Exception $e) {
            die("DB error: {$e->getMessage()}");
        }
    }
   
    public function __destruct()
    {
        // clean up
        oci_close($this->conn);
    }
   
    // This function creates and executes a SQL select statement and returns an array as the result
    // 2-dimensional array: the result array contains nested arrays (each contains the data of a single row)
    public function selectAllAgents()
    {
        $sql = "SELECT a.A_ID, e.FIRST_NAME, e.LAST_NAME, e.E_ID, a.CAPABILITY_LEVEL, a.A_ROLE FROM AGENT a JOIN EMPLOYEE e ON a.E_ID=e.E_ID ORDER BY a.A_ID";

        // oci_parse(...) prepares the Oracle statement for execution
        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
   
        // Fetches multiple rows from a query into a two-dimensional array
        // Parameters of oci_fetch_all:
        //   $statement: must be executed before
        //   $res: will hold the result after the execution of oci_fetch_all
        //   $skip: it's null because we don't need to skip rows
        //   $maxrows: it's null because we want to fetch all rows
        //   $flag: defines how the result is structured: 'by rows' or 'by columns'
        //      OCI_FETCHSTATEMENT_BY_ROW (The outer array will contain one sub-array per query row)
        //      OCI_FETCHSTATEMENT_BY_COLUMN (The outer array will contain one sub-array per query column. This is the default.)
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
   
        //clean up;
        oci_free_statement($statement);
   
        return $res;
    }

    public function selectIndivGadgets($a_id){

        $sql = "SELECT t.T_ID, t.DESCRIPTION, t.AMOUNT FROM TOOL t JOIN agent a ON t.FK_A_ID=a.A_ID WHERE t.FK_A_ID = $a_id ORDER BY t.T_ID";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
   
        //clean up;
        oci_free_statement($statement);
   
        return $res;

    }

    public function selectSpecificTool($t_id){

        $sql = "SELECT t.FK_A_ID, t.T_ID, t.DESCRIPTION, t.AMOUNT FROM TOOL t WHERE t.T_ID = $t_id";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
   
        //clean up;
        oci_free_statement($statement);
   
        return $res;

    }

    public function insertAddTool($a_id, $desc, $amount){

        $sql = "INSERT INTO TOOL (FK_A_ID, DESCRIPTION, AMOUNT) VALUES ($a_id, '{$desc}', '{$amount}')";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);
        oci_free_statement($statement);
        return $success;

    }
    
    public function updateSpecificTool($t_id, $a_id, $desc, $amount){

        $sql = "UPDATE TOOL SET FK_A_ID = $a_id, T_ID = $t_id, DESCRIPTION = '$desc', AMOUNT = $amount WHERE T_ID = $t_id";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);
        oci_free_statement($statement);
        return $success;

    }

    public function deleteTool($t_id){

        $sql = "DELETE FROM TOOL WHERE T_ID = $t_id";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);
        oci_free_statement($statement);
        return $success;

    }

    public function selectIndivMissions($a_id){

        $sql = "SELECT m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME FROM MISSIONLOG m JOIN TAKES_ON t ON m.M_ID=t.FK_M_ID JOIN AGENT a ON a.A_ID=t.FK_A_ID LEFT JOIN EXTERN_PARTNER ep ON m.FK_P_ID=ep.P_ID JOIN SUBJECT s ON m.FK_S_ID=s.S_ID WHERE t.FK_A_ID = $a_id ORDER BY m.M_ID";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
   
        //clean up;
        oci_free_statement($statement);
   
        return $res;

    }

    

    public function insertMission($a_id, $m_code, $desc, $m_date, $m_going, $m_subjects, $m_partners){

        $sql = "INSERT INTO MISSIONLOG (CODENAME, DESCRIPTION, M_DATE, ONGOING, FK_S_ID, FK_P_ID) VALUES('$m_code', '$desc', TO_DATE('$m_date', 'YYYY-MM-DD'), $m_going, $m_subjects, $m_partners)";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);

        oci_free_statement($statement);
        return $success;

    }

    public function insertTakesOn($a_id){

        $sql = "SELECT M_ID FROM MISSIONLOG ORDER BY M_ID DESC FETCH NEXT 1 ROWS ONLY";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        $res = oci_fetch_array($statement, OCI_FETCHSTATEMENT_BY_ROW);

        $sql = "INSERT INTO TAKES_ON (FK_A_ID, FK_M_ID) VALUES($a_id, ".$res['M_ID'].")";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);

        oci_free_statement($statement);
        return $success;
    
    }

    
    public function selectSpecificMission($m_id){

        $sql = "SELECT * FROM MISSIONLOG WHERE M_ID = $m_id";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
    
        //clean up;
        oci_free_statement($statement);
    
        return $res;

    }

    
    public function updateSpecificMission($m_id, $m_code, $desc, $m_date, $m_going, $m_sub, $m_partner){

        if($m_partner != 'null'){
            $sql = "UPDATE MISSIONLOG 
                    SET CODENAME = '$m_code', 
                    DESCRIPTION = '$desc', 
                    M_DATE=TO_DATE('$m_date','YYYY-MM-DD'), 
                    ONGOING=$m_going, 
                    FK_S_ID=$m_sub, 
                    FK_P_ID=$m_partner 
                    WHERE M_ID = $m_id";
        } else {
            $sql = "UPDATE MISSIONLOG 
                    SET CODENAME = '$m_code', 
                    DESCRIPTION = '$desc', 
                    M_DATE=TO_DATE('$m_date','YYYY-MM-DD'), 
                    ONGOING=$m_going, 
                    FK_S_ID=$m_sub, 
                    FK_P_ID= null 
                    WHERE M_ID = $m_id";
        }

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);
        oci_free_statement($statement);
        return $success;

    }

    public function deleteMission($m_id){

        $sql = "DELETE FROM TAKES_ON WHERE FK_M_ID = $m_id";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn);
        
        oci_free_statement($statement);

        $sql = "DELETE FROM MISSIONLOG WHERE M_ID = $m_id";

        $statement = oci_parse($this->conn, $sql);
        $success = oci_execute($statement) && oci_commit($this->conn) && $success;

        oci_free_statement($statement);
        return $success;

    }



    public function selectAllSubjects()
    {
        $sql = "SELECT S_ID, FIRST_NAME, LAST_NAME FROM SUBJECT ORDER BY S_ID";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
   
        //clean up;
        oci_free_statement($statement);
   
        return $res;
    }


    public function selectAllPartners()
    {
        $sql = "SELECT P_ID, NAME FROM EXTERN_PARTNER ORDER BY P_ID";

        $statement = oci_parse($this->conn, $sql);
        oci_execute($statement);
        oci_fetch_all($statement, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
   
        //clean up;
        oci_free_statement($statement);
   
        return $res;
    }



    // stored procedure aufruf

    public function countAgentTools($a_id)
    {
         // It is not necessary to assign the output variable,
        // but to be sure that the $errorcode differs after the execution of our procedure we do it anyway
        $res = PHP_INT_MAX;
        //var_dump($res);

        $sql = 'BEGIN AgentToolCnt(:a_id, :res); END;';
        $statement = oci_parse($this->conn, $sql);
   
        oci_bind_by_name($statement, ':a_id', $a_id);
        oci_bind_by_name($statement, ':res', $res);
   
        oci_execute($statement);
   
        oci_free_statement($statement);
   
        //var_dump($res);
        return $res;
    }
    

}