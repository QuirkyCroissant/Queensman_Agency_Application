<?php

class DatabaseHelper
{
    const username = 'root';
    const password = 'Schikuta<3';
    const host = 'mysqldb';
    # port needed to be changed to internal listening port number to be able to 
    # communicate with docker database in internal network
    const port = '3306';
    const dbname = 'mysql_queensmandb';

    protected $conn;

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

    public function selectIndivGadgets($a_id)
    {
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

    public function selectSpecificTool($t_id)
    {
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
        $sql = "SELECT m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, s.FIRST_NAME, s.LAST_NAME, s.COI, ep.NAME FROM MISSIONLOG m JOIN TAKES_ON t ON m.M_ID=t.FK_M_ID JOIN AGENT a ON a.A_ID=t.FK_A_ID LEFT JOIN EXTERN_PARTNER ep ON m.FK_P_ID=ep.P_ID JOIN SUBJECT s ON m.FK_S_ID=s.S_ID WHERE t.FK_A_ID = ? ORDER BY m.M_ID";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $a_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $res;
    }

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