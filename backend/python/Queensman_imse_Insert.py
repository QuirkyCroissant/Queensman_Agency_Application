import random
from collections import defaultdict
from datetime import datetime, timedelta
from tqdm import tqdm

import mysql.connector
import pandas as pd
from faker import Faker

username = "root"  # db user name
password = "pilguin"  # db password
host = "localhost"  # db host
database = "QUEENSMAN"  # db name

# Consider using with to avoid open connections
connection = mysql.connector.connect(user=username, password=password, host=host, database=database, port=3307)
curs = connection.cursor()

fake = Faker('en_GB')
fake_international = Faker(['de_DE', 'es_ES', 'fr_FR', 'ru_RU', 'en_GB'])

# Amount of auto generated test cases for each table
row_pc_cnt = 70         # post_code
row_emp_cnt = 5000      # employee
row_a_cnt = 1200        # agent
row_analy_cnt = 2000    # analyst
# tool has randomized entries 2-3x for each agent
# facility type table consists of x entries of a list that will be inserted in random order
row_branch_cnt = 50     # branch
row_s_cnt = 1000        # subject

# Check and populate POST_CODE table
sql = "SELECT * FROM POST_CODE"
curs.execute(sql)
result = curs.fetchall()

if len(result) != row_pc_cnt:
    print("################ POST_CODE-TABLE ################")
    fake_post_code = defaultdict(list)

    for _ in tqdm(range(row_pc_cnt)):
        if _ != 20:
            fake_post_code["post_code"].append(fake.postcode())
            fake_post_code["city"].append(fake.city())
        else:
            fake_post_code["post_code"].append("NW1 6XE")
            fake_post_code["city"].append("London")

    df_fake_post_code = pd.DataFrame(fake_post_code)
    print(df_fake_post_code.info())

    rows = [tuple(x) for x in df_fake_post_code.values]
    curs.executemany("INSERT INTO POST_CODE (POST_CODE, CITY) VALUES (%s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(row_pc_cnt) + " ROWS IN POST_CODE! \n")

# Check and populate EMPLOYEE table
sql = "SELECT * FROM EMPLOYEE"
curs.execute(sql)
result = curs.fetchall()

if len(result) != row_emp_cnt:
    print("################ EMPLOYEE-TABLE ################")
    fake_employee = defaultdict(list)

    for _ in range(row_emp_cnt):
        street = ""
        pc_fk = 0

        if _ == 68:
            first_n = "Lucas"
            last_n = "Domonik"
        elif _ == 21:
            first_n = "Dorian"
            last_n = "Hijack"
        elif _ == 13:
            first_n = "Harry"
            last_n = "Hart"
        elif _ == 14:
            first_n = "Gary"
            last_n = "Unwin"
        elif _ == 41:
            first_n = "Benoit"
            last_n = "Blanc"
        elif _ == 220:
            first_n = "Sherlock"
            last_n = "Holmes"
            street = "Baker Street"
            pc_fk = 21
        else:
            first_n = fake.first_name()
            last_n = fake.last_name()

        fake_employee["first_name"].append(first_n)
        fake_employee["last_name"].append(last_n)
        fake_employee["telephone_number"].append(fake.msisdn())

        if street != "":
            fake_employee["street"].append(street)
        else:
            fake_employee["street"].append(fake.street_name())

        fake_employee["email"].append(first_n + "." + last_n + '@' + fake.free_email_domain())
        if pc_fk == 0:
            fake_employee["fk_post_code"].append(fake.random_int(min=1, max=40))
        else:
            fake_employee["fk_post_code"].append(pc_fk)

    df_fake_employee = pd.DataFrame(fake_employee)
    print(df_fake_employee.info())

    rows = [tuple(x) for x in df_fake_employee.values]
    curs.executemany("INSERT INTO EMPLOYEE (FIRST_NAME, LAST_NAME, TELEPHONE_NUMBER, STREET, EMAIL_ADDRESS, FK_POST_CODE) VALUES (%s, %s, %s, %s, %s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(row_emp_cnt) + " ROWS IN EMPLOYEE!\n")

# Check and populate AGENT table
sql = "SELECT * FROM EMPLOYEE"
curs.execute(sql)
temp_res = curs.fetchall()
amount_emp = len(temp_res)  # relevant for the agents

sql = "SELECT * FROM AGENT"
curs.execute(sql)
result = curs.fetchall()

professions = ("Intelligence", "Counter Intelligence", "Protective Intelligence", "Specialized Resources", "Cyber and Crime", "Counter Terrorism")
levels = ['S', 'A', 'B', 'C']
fk_agents_e_id = set()  # a dataset for foreign keys, that combine agents with the primary key of the employees

if len(result) != row_a_cnt:
    print("################ AGENT-TABLE ################")
    fake_agent = defaultdict(list)

    if amount_emp < 20:
        row_a_cnt = amount_emp

    fk_agents_e_id.add(69)  # "Lucas Domonik"
    fk_agents_e_id.add(22)  # "Dorian Hijack"
    fk_agents_e_id.add(14)  # Harry Hart (Galahad)
    fk_agents_e_id.add(15)  # "Gary Unwin" (Eggsy)
    fk_agents_e_id.add(42)  # Benoit Blanc
    fk_agents_e_id.add(221)  # Sherlock Holmes

    while len(fk_agents_e_id) < row_a_cnt:
        fk_agents_e_id.add(random.randint(1, row_emp_cnt))

    for elem in fk_agents_e_id:
        if elem == 69:
            fake_agent["e_id"].append(69)
            fake_agent["capability_lvl"].append("S2")
            fake_agent["a_role"].append("Intelligence")
        elif elem == 22:
            fake_agent["e_id"].append(22)
            fake_agent["capability_lvl"].append("A1")
            fake_agent["a_role"].append("Protective Intelligence")
        elif elem == 14:
            fake_agent["e_id"].append(14)
            fake_agent["capability_lvl"].append("S2")
            fake_agent["a_role"].append("Counter Terrorism")
        elif elem == 15:
            fake_agent["e_id"].append(15)
            fake_agent["capability_lvl"].append("S1")
            fake_agent["a_role"].append("Counter Terrorism")
        elif elem == 42:
            fake_agent["e_id"].append(42)
            fake_agent["capability_lvl"].append("A2")
            fake_agent["a_role"].append("Cyber & Crime")
        elif elem == 221:
            fake_agent["e_id"].append(221)
            fake_agent["capability_lvl"].append("B1")
            fake_agent["a_role"].append("Cyber & Crime")
        else:
            fake_agent["e_id"].append(elem)
            fake_agent["capability_lvl"].append(random.choice(levels) + str(random.randint(1, 2)))
            fake_agent["a_role"].append(random.choice(professions))

    df_fake_agent = pd.DataFrame(fake_agent)
    print(df_fake_agent.info())

    rows = [tuple(x) for x in df_fake_agent.values]
    curs.executemany("INSERT INTO AGENT (E_ID, CAPABILITY_LEVEL, A_ROLE) VALUES (%s, %s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(row_a_cnt) + " ROWS IN AGENT! \n")

# Check and populate ANALYST table
sql = "SELECT * FROM ANALYST"
curs.execute(sql)
result = curs.fetchall()

specs = ("Forensic Ballistics", "Toolmarks", "DNA", "Fire and Explosion Debris", "Controlled Substances", "Trace Evidence", "Wildlife", "Digital and Multimedia Science")

if len(result) != row_analy_cnt:
    print("################ ANALYST-TABLE ################")
    fk_analyst_e_id = set()

    while len(fk_analyst_e_id) < row_analy_cnt:
        rng_num = random.randint(1, row_emp_cnt)
        if rng_num not in fk_agents_e_id:
            fk_analyst_e_id.add(rng_num)

    fake_analyst = defaultdict(list)
    for elem in fk_analyst_e_id:
        fake_analyst["e_id"].append(elem)
        fake_analyst["specialisation"].append(random.choice(specs))

    df_fake_analyst = pd.DataFrame(fake_analyst)
    print(df_fake_analyst.info())

    rows = [tuple(x) for x in df_fake_analyst.values]
    curs.executemany("INSERT INTO ANALYST (E_ID, SPECIALISATION) VALUES (%s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(row_analy_cnt) + " ROWS IN ANALYST! \n")

# Check and populate TOOL table
sql = "SELECT * FROM TOOL"
curs.execute(sql)
result = curs.fetchall()

cars_list = ["Aston Martin DB5", "Aston Martin DBS", "Pinzgauer 716M", "Amphicar 770", "Shaguar E-Type", "VW Golf GTI", "Nissan Skyline GTR R34"]
weapons_list = ["Explosive Pen", "Handgun", "Throwing Knife", "Nanchaku", "Pistol Suit Case", "MP5"]
gadget_list = ["Spy Glasses", "Spy Umbrella", "Agent Suit", "Eavesdrop Bug", "Fake Newspaper", "Multi-Vitamin pill"]

sql = "SELECT A_ID, CAPABILITY_LEVEL, A_ROLE FROM AGENT"
curs.execute(sql)
a_id_list = curs.fetchall()

if len(result) == 0:
    print("################ TOOL-TABLE ################")
    fake_tool = defaultdict(list)

    for elem in a_id_list:
        if elem[2] == "Counter Terrorism" or elem[2] == "Intelligence" or elem[2] == "Counter Intelligence":
            fake_tool["fk_a_id"].append(elem[0])
            fake_tool["description"].append(random.choice(cars_list))
            fake_tool["amount"].append("1")

        if elem[1][0] == 'A':
            fake_tool["fk_a_id"].append(elem[0])
            fake_tool["description"].append(random.choice(weapons_list))
            fake_tool["amount"].append(str(random.randint(1, 3)))

        elif elem[1][0] == 'S':
            fake_tool["fk_a_id"].append(elem[0])
            fake_tool["description"].append("Explosive Pen")
            fake_tool["amount"].append(str(random.randint(1, 3)))

            fake_tool["fk_a_id"].append(elem[0])
            fake_tool["description"].append(random.choice(weapons_list))
            fake_tool["amount"].append(str(random.randint(1, 3)))

        gadget_cnt = random.randint(1, 4)
        temp_list = gadget_list
        random.shuffle(temp_list)

        for i in range(gadget_cnt):
            fake_tool["fk_a_id"].append(elem[0])
            fake_tool["description"].append(temp_list[i])
            fake_tool["amount"].append(str(random.randint(1, 3)))

    df_fake_tool = pd.DataFrame(fake_tool)
    print(df_fake_tool.info())

    rows = [tuple(x) for x in df_fake_tool.values]
    curs.executemany("INSERT INTO TOOL (FK_A_ID, DESCRIPTION, AMOUNT) VALUES (%s, %s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(len(fake_tool)) + " ROWS IN TOOL! \n")

# Check and populate FACILITY_TYPE table
sql = "SELECT * FROM FACILITY_TYPE"
curs.execute(sql)
result = curs.fetchall()

if len(result) == 0:
    print("################ FACILITY_TYPE-TABLE ################")
    fac_list = ["Headquarters", "Office Facility", "Research and Tech Facility", "Hideout", "Training Facility", "Logistics Facility", "Medical Facility"]
    random.shuffle(fac_list)

    fake_fac_type = defaultdict(list)
    for f in fac_list:
        fake_fac_type["type"].append(f)

    df_fake_facility_type = pd.DataFrame(fake_fac_type)
    print(df_fake_facility_type.info())

    rows = [tuple(x) for x in df_fake_facility_type.values]
    curs.executemany("INSERT INTO FACILITY_TYPE (TYPE) VALUES (%s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(len(fac_list)) + " ROWS IN FACILITY_TYPE!\n")

sql = "SELECT * FROM FACILITY_TYPE"
curs.execute(sql)
row_facility_t_cnt = len(curs.fetchall())

# Check and populate BRANCH table
sql = "SELECT * FROM BRANCH"
curs.execute(sql)
result = curs.fetchall()

if len(result) != row_branch_cnt:
    print("################ BRANCH-TABLE ################")

    sql = "SELECT FT_ID FROM FACILITY_TYPE WHERE TYPE = 'Hideout'"
    curs.execute(sql)
    hideout_id = curs.fetchone()[0]
    facility_type_ids = list(range(1, row_facility_t_cnt + 1))
    facility_type_ids.remove(hideout_id)

    fake_branch = defaultdict(list)
    for _ in range(row_branch_cnt):
        num = random.randint(1, 4)
        name = "xxx"

        if num == 1:
            name = random.choice(["Building", "House"]) + " No " + str(random.randint(1, 300))
        elif num == 2:
            name = fake.last_name() + " " + random.choice(["Building", "Compound", "Barracks", "Hall", "Park", "Tower"])
        elif num == 3:
            name = fake.last_name() + "-" + fake.last_name() + " " + random.choice(["Building", "House"])
        else:
            name = fake.first_name() + "'s House"

        fake_branch["name"].append(name)
        fake_branch["street"].append(fake.street_name())

        if num != 1:  # if name starts with House/Building, then it's a hideout
            fake_branch["fk_type"].append(str(random.choice(facility_type_ids)))
        else:
            fake_branch["fk_type"].append(str(hideout_id))

        fake_branch["fk_pc"].append(random.randint(1, row_pc_cnt))

    df_fake_branch = pd.DataFrame(fake_branch)
    print(df_fake_branch.info())

    rows = [tuple(x) for x in df_fake_branch.values]
    curs.executemany("INSERT INTO BRANCH (NAME, STREET, FK_TYPE, FK_POST_CODE) VALUES (%s, %s, %s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(row_branch_cnt) + " ROWS IN BRANCH!\n")

# Check and populate SUBJECT table
sql = "SELECT * FROM SUBJECT"
curs.execute(sql)
result = curs.fetchall()

if len(result) != row_s_cnt:
    print("################ SUBJECT-TABLE ################")
    fake_subject = defaultdict(list)

    coi_list = ["White-Collar", "Terrorism", "Espionage", "Cyber", "Drugs"]

    for _ in range(row_s_cnt):
        if _ == 9:
            fake_subject["first_name"].append("George")
            fake_subject["last_name"].append("Sonett")
            fake_subject["coi"].append("White-Collar")
        elif _ == 11:
            fake_subject["first_name"].append("Edgar")
            fake_subject["last_name"].append("Zhykuter")
            fake_subject["coi"].append("Espionage")
        elif _ == 13:
            fake_subject["first_name"].append("Herbert")
            fake_subject["last_name"].append("Vonek")
            fake_subject["coi"].append("Cyber")
        else:
            fake_subject["first_name"].append(fake_international.first_name())
            fake_subject["last_name"].append(fake_international.last_name())
            fake_subject["coi"].append(random.choice(coi_list))

    df_fake_subject = pd.DataFrame(fake_subject)
    print(df_fake_subject.info())

    rows = [tuple(x) for x in df_fake_subject.values]
    curs.executemany("INSERT INTO SUBJECT (FIRST_NAME, LAST_NAME, COI) VALUES (%s, %s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(row_s_cnt) + " ROWS IN SUBJECT! \n")

# Check and populate EXTERN_PARTNER table
sql = "SELECT * FROM EXTERN_PARTNER"
curs.execute(sql)
result = curs.fetchall()

if len(result) == 0:
    print("################ EXTERN_PARTNER-TABLE ################")
    fake_partner = defaultdict(list)

    partner_list = [
        ("Central Intelligence Agency", "contact@cia.gov.com"),
        ("Federal Bureau of Investigation", "contact@fbi.gov.com"),
        ("Interpol International", "contact@interpol.com"),
        ("Secret Intelligence Service", "contact@sis.gov.uk"),
        ("Mossad", "contact@mossad.gov.il"),
        ("Defence Intelligence Agency", "contact@dia.gov.com")
    ]
    random.shuffle(partner_list)

    country_list = ['Austrian', 'Belgian', 'Bulgarian', 'Croatian', 'Cypriot', 'Czech', 'Danish',
                    'Estonian', 'Finnish', 'French', 'German', 'Greek', 'Hungarian', 'Irish',
                    'Italian', 'Latvian', 'Lithuanian', 'Luxembourgian', 'Maltese', 'Dutch',
                    'Polish', 'Portugese', 'Romanian', 'Slovakian', 'Slovenian', 'Spanish', 'Swedish']
    random.shuffle(country_list)

    for elem in partner_list:
        fake_partner["name"].append(elem[0])
        fake_partner["contact"].append(elem[1])

    for p in country_list:
        fake_partner["name"].append(p + " Police Force")
        fake_partner["contact"].append(fake.ascii_safe_email())

    df_fake_partner = pd.DataFrame(fake_partner)
    print(df_fake_partner.info())

    rows = [tuple(x) for x in df_fake_partner.values]
    curs.executemany("INSERT INTO EXTERN_PARTNER (NAME, CONTACT) VALUES (%s, %s)", rows)
    connection.commit()

    print("SUCCESS; INSERTED " + str(len(partner_list) + len(country_list)) + " ROWS IN EXTERN_PARTNER! \n")

# Check and populate ASSIGNED_TO table
sql = "SELECT * FROM ASSIGNED_TO"
curs.execute(sql)
result = curs.fetchall()

if len(result) == 0:
    print("################ ASSIGNED_TO-TABLE ################")
    fake_assigned = defaultdict(list)

    sql = "SELECT E_ID FROM EMPLOYEE"
    curs.execute(sql)
    emp_ids = curs.fetchall()  # employees count

    sql = "SELECT B_ID FROM BRANCH"
    curs.execute(sql)
    branch_ids = curs.fetchall()  # branch buildings count

    for _ in range(len(emp_ids)):
        fake_assigned["ass_e_id"].append(_ + 1)
        fac_key = int(random.choice(branch_ids)[0])
        fake_assigned["ass_b_id"].append(fac_key)
        fake_assigned["since"].append((fake.date_between(datetime(2002, 6, 30), datetime(2010, 2, 3))).strftime('%Y-%m-%d'))
        fake_assigned["till"].append(None)

    df_fake_assigned = pd.DataFrame(fake_assigned)
    print(df_fake_assigned.info())

    rows = [tuple(x) for x in df_fake_assigned.values]
    curs.executemany("INSERT INTO ASSIGNED_TO (ASS_E_ID, ASS_B_ID, SINCE, TILL) VALUES (%s, %s, %s, %s)", rows)
    connection.commit()
    print("SUCCESS; INSERTED " + str(len(df_fake_assigned)) + " ROWS IN ASSIGNED_TO!\n")

    transfer_number = int(len(emp_ids) * 0.2)
    transferees = []

    while len(transferees) < transfer_number:
        t_id = random.randint(1, len(emp_ids))
        if t_id not in transferees:
            transferees.append(t_id)

    fake_transfers = defaultdict(list)

    for id in transferees:
        sql = "SELECT * FROM ASSIGNED_TO WHERE ASS_E_ID = " + str(id)
        curs.execute(sql)
        rows_of_interest = curs.fetchall()

        old_branch_id = rows_of_interest[0][2]
        old_since_date = rows_of_interest[0][3]

        transfer_date = datetime(2000, 1, 1)
        while transfer_date < old_since_date:
            transfer_date = datetime.combine(fake.date_between(datetime(2004, 1, 1), datetime(2015, 12, 22)), datetime.min.time())

        transfer_date = transfer_date.strftime('%Y-%m-%d')
        change_stmt = "UPDATE ASSIGNED_TO SET TILL=%s WHERE ASS_E_ID=%s AND ASS_B_ID=%s"
        curs.execute(change_stmt, (transfer_date, id, old_branch_id))
        connection.commit()

        fake_transfers["ass_e_id"].append(str(id))
        fac_key = old_branch_id
        while fac_key == old_branch_id:
            fac_key = int(random.choice(branch_ids)[0])

        fake_transfers["ass_b_id"].append(fac_key)
        fake_transfers["since"].append(transfer_date)
        fake_transfers["till"].append(None)

    df_fake_transfers = pd.DataFrame(fake_transfers)
    print(df_fake_transfers.info())

    rows = [tuple(x) for x in df_fake_transfers.values]
    curs.executemany("INSERT INTO ASSIGNED_TO (ASS_E_ID, ASS_B_ID, SINCE, TILL) VALUES (%s, %s, %s, %s)", rows)
    connection.commit()

    print("SUCCESS; ALTERED (TRANSFERRED) & INSERTED " + str(len(df_fake_transfers)) + " ROWS IN ASSIGNED_TO! \n")

# Check and populate MISSIONLOG table
sql = "SELECT * FROM MISSIONLOG"
curs.execute(sql)
result = curs.fetchall()

if len(result) == 0:
    print("################ MISSIONLOG-TABLE ################")
    fake_missions = defaultdict(list)

    sql = "SELECT S_ID FROM SUBJECT"
    curs.execute(sql)
    s_ids = curs.fetchall()  # subject ids

    sql = "SELECT * FROM EXTERN_PARTNER"
    curs.execute(sql)
    ex_p_ids = curs.fetchall()  # extern partner ids

    m_names = []
    while len(m_names) < len(s_ids):
        n = random.randint(0, 99999)
        if n not in m_names:
            m_names.append(n)

    for _ in range(len(s_ids)):
        fake_missions["codename"].append("Case File #" + str(m_names[_]).rjust(5, "0"))
        fake_missions["description"].append(fake.paragraph(nb_sentences=5))

        mission_date = fake.date_between(datetime(2003, 5, 30), datetime.now())
        fake_missions["m_date"].append(mission_date.strftime('%Y-%m-%d'))

        if datetime.combine(mission_date, datetime.min.time()) > datetime(2014, 6, 6):
            if random.randint(1, 4) == 4:
                fake_missions["ongoing"].append(1)
            else:
                fake_missions["ongoing"].append(0)
        else:
            fake_missions["ongoing"].append(0)

        fake_missions["fk_s_id"].append(_ + 1)
        partner_key = random.choice(ex_p_ids)[0]

        if random.randint(1, 4) != 4:
            fake_missions["fk_p_id"].append(str(partner_key))
        else:
            fake_missions["fk_p_id"].append(None)

    df_fake_missions = pd.DataFrame(fake_missions)
    print(df_fake_missions.info())

    rows = [tuple(x) for x in df_fake_missions.values]
    curs.executemany("INSERT INTO MISSIONLOG (CODENAME, DESCRIPTION, M_DATE, ONGOING, FK_S_ID, FK_P_ID) VALUES (%s, %s, %s, %s, %s, %s)", rows)
    connection.commit()

# Check and populate TAKES_ON table
sql = "SELECT * FROM TAKES_ON"
curs.execute(sql)
result = curs.fetchall()

if len(result) == 0:
    print("################ TAKES_ON ################")

    m_sql = "SELECT M_ID, CODENAME, S_ID, LAST_NAME, COI FROM MISSIONLOG JOIN SUBJECT ON FK_S_ID=S_ID"
    curs.execute(m_sql)
    m_list = curs.fetchall()

    ct_a_sql = "SELECT A_ID, A_ROLE FROM AGENT WHERE A_ROLE = 'Counter Terrorism'"
    curs.execute(ct_a_sql)
    ct_a_list = curs.fetchall()

    a_sql = "SELECT A_ID, A_ROLE FROM AGENT WHERE A_ROLE NOT LIKE 'Counter Terrorism'"
    curs.execute(a_sql)
    a_list = curs.fetchall()

    takes_on_list = []

    for _ in m_list:
        if _[4] == "Terrorism":
            agent_cnt = random.randint(5, 10)
            for i in range(agent_cnt):
                takes_on_list.append((random.choice(ct_a_list)[0], _[0]))
        else:
            agent_cnt = random.randint(3, 5)
            for i in range(agent_cnt):
                takes_on_list.append((random.choice(a_list)[0], _[0]))

    rows = [tuple(x) for x in takes_on_list]
    curs.executemany("INSERT INTO TAKES_ON (FK_A_ID, FK_M_ID) VALUES (%s, %s)", rows)
    connection.commit()

    print("SUCCESS; ROWS INSERTED IN TAKES_ON!\n")

# Update SUPERIOR_FS in EMPLOYEE table
print("################ SUPERIOR_FS ################")
curs.execute("SELECT * FROM EMPLOYEE")
result = curs.fetchall()

if len(result) != 0:
    curs.execute("SELECT a.ASS_B_ID, e.E_ID FROM EMPLOYEE e JOIN ASSIGNED_TO a ON e.E_ID=a.ASS_E_ID")
    emp_list = curs.fetchall()

    section_list = defaultdict(list)
    for b in range(len(emp_list)):
        section_list[emp_list[b][0]].append(emp_list[b][1])

    chosen_ones = defaultdict(list)
    for _ in section_list:
        leader = str(random.choice(section_list[_]))
        chosen_ones[_].append(leader)

    for e in emp_list:
        leader = (chosen_ones.get(e[0]))[0]
        if leader != str(e[1]):
            change_stmt = "UPDATE EMPLOYEE SET SUPERIOR_FS=%s WHERE E_ID=%s"
            curs.execute(change_stmt, (leader, str(e[1])))
            connection.commit()
