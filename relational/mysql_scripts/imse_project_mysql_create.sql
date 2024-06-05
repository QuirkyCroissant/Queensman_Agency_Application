CREATE TABLE POST_CODE(
    PC_ID INT AUTO_INCREMENT PRIMARY KEY,
    POST_CODE VARCHAR(20),
    CITY VARCHAR(50)
);

CREATE TABLE EMPLOYEE(
    E_ID INT AUTO_INCREMENT PRIMARY KEY,
    FIRST_NAME VARCHAR(50) NOT NULL,
    LAST_NAME VARCHAR(50) NOT NULL,
    TELEPHONE_NUMBER VARCHAR(30),
    STREET VARCHAR(40),
    EMAIL_ADDRESS VARCHAR(100),
    FK_POST_CODE INT,
    SUPERIOR_FS INT,
    
    FOREIGN KEY (SUPERIOR_FS) REFERENCES EMPLOYEE(E_ID),
    FOREIGN KEY (FK_POST_CODE) REFERENCES POST_CODE(PC_ID)
);

CREATE TABLE AGENT(
    E_ID INT NOT NULL,
    A_ID INT AUTO_INCREMENT NOT NULL,
    CAPABILITY_LEVEL VARCHAR(5),
    A_ROLE VARCHAR(45) DEFAULT 'ESP-OPERATOR',
    
    FOREIGN KEY (E_ID) REFERENCES EMPLOYEE(E_ID),
    CONSTRAINT UK_AGENT UNIQUE (A_ID),
    PRIMARY KEY (E_ID)
);

CREATE TABLE ANALYST(
    E_ID INT PRIMARY KEY,
    SPECIALISATION VARCHAR(70),
    
    FOREIGN KEY (E_ID) REFERENCES EMPLOYEE(E_ID)
);

CREATE TABLE TOOL(
    T_ID INT AUTO_INCREMENT PRIMARY KEY,
    FK_A_ID INT,
    DESCRIPTION TEXT,
    AMOUNT INT CHECK (AMOUNT < 4),
    
    FOREIGN KEY (FK_A_ID) REFERENCES AGENT(A_ID)
);

CREATE TABLE FACILITY_TYPE (
    FT_ID INT AUTO_INCREMENT PRIMARY KEY,
    TYPE VARCHAR(100) NOT NULL
);

CREATE TABLE BRANCH(
    B_ID INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(60),
    STREET VARCHAR(55),
    FK_TYPE INT,
    FK_POST_CODE INT,
    
    FOREIGN KEY (FK_TYPE) REFERENCES FACILITY_TYPE(FT_ID),
    FOREIGN KEY (FK_POST_CODE) REFERENCES POST_CODE(PC_ID)
);

CREATE TABLE ASSIGNED_TO(
    ASS_ID INT AUTO_INCREMENT,
    ASS_E_ID INT,
    ASS_B_ID INT,
    SINCE DATE,
    TILL DATE,
    
    FOREIGN KEY (ASS_E_ID) REFERENCES EMPLOYEE(E_ID),
    FOREIGN KEY (ASS_B_ID) REFERENCES BRANCH(B_ID),
    PRIMARY KEY (ASS_ID, ASS_E_ID, ASS_B_ID, SINCE)
);

CREATE TABLE SUBJECT(
    S_ID INT AUTO_INCREMENT PRIMARY KEY,
    FIRST_NAME VARCHAR(45),
    LAST_NAME VARCHAR(45),
    COI VARCHAR(100)
);

CREATE TABLE EXTERN_PARTNER(
    P_ID INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(55),
    CONTACT VARCHAR(50)
);

CREATE TABLE MISSIONLOG(
    M_ID INT AUTO_INCREMENT PRIMARY KEY,
    CODENAME VARCHAR(255),
    DESCRIPTION TEXT,
    M_DATE DATE,
    ONGOING TINYINT(1),
    FK_S_ID INT NOT NULL,
    FK_P_ID INT,
    
    FOREIGN KEY (FK_S_ID) REFERENCES SUBJECT(S_ID),
    FOREIGN KEY (FK_P_ID) REFERENCES EXTERN_PARTNER(P_ID),
    
    CONSTRAINT CHK_MISSIONLOG_ONGOING CHECK (ONGOING = 0 OR ONGOING = 1)
);

CREATE TABLE TAKES_ON(
    TO_ID INT AUTO_INCREMENT PRIMARY KEY,
    FK_A_ID INT NOT NULL,
    FK_M_ID INT NOT NULL,
    
    FOREIGN KEY (FK_A_ID) REFERENCES AGENT(A_ID),
    FOREIGN KEY (FK_M_ID) REFERENCES MISSIONLOG(M_ID)
);

-- Trigger for INSERTS/UPDATES ON MISSIONLOG.CODENAME: Attribute has to begin with 'Case File #'
DELIMITER //

CREATE TRIGGER trigger_codename
    BEFORE INSERT ON MISSIONLOG
    FOR EACH ROW
    BEGIN
        IF NEW.CODENAME NOT LIKE 'Case File #%' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Codename must begin with "Case File #"';
        END IF;
    END//

DELIMITER ;

-- Procedure that counts all the tools for a given agent
DELIMITER //
CREATE PROCEDURE AgentToolCnt(
        IN agent_id INT,
        OUT cnt INT
    )
    BEGIN
        SELECT SUM(AMOUNT) INTO cnt
        FROM TOOL
        WHERE FK_A_ID = agent_id;
    END//
DELIMITER ;
