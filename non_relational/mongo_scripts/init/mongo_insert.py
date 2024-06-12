from pymongo import MongoClient
import mysql.connector
from datetime import datetime, date

# Connect to MongoDB
client = MongoClient('mongodb://localhost:27017/')
db = client['queensmandb']

# Drop collections if they already exist
db.employees.drop()
db.branches.drop()
db.missionlogs.drop()
db.subjects.drop()
db.external_partners.drop()

# Connect to MySQL
mysql_conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='Schikuta<3',
    database='mysql_queensmandb',
    port=3307
)
cursor = mysql_conn.cursor(dictionary=True)

# Converter to transform date into datetime(the former is not support by json)
def convert_date_to_datetime(d):
    if isinstance(d, date):  # Using date from datetime module
        return datetime.combine(d, datetime.min.time())
    return d

# Fetch and create employees data
cursor.execute("""
    SELECT e.*, pc.*, a.A_ID as agent_id, a.CAPABILITY_LEVEL, t.T_ID as tool_id, t.DESCRIPTION as tool_description, t.AMOUNT as tool_amount, 
           an.E_ID as analyst_id, an.SPECIALISATION, an.YEARS_OF_EXPERIENCE, at.ASS_B_ID as branch_id, at.SINCE, at.TILL
    FROM EMPLOYEE e
    LEFT JOIN AGENT a ON e.E_ID = a.E_ID
    LEFT JOIN TOOL t ON a.A_ID = t.FK_A_ID
    LEFT JOIN ANALYST an ON e.E_ID = an.E_ID
    LEFT JOIN ASSIGNED_TO at ON e.E_ID = at.ASS_E_ID
    LEFT JOIN POST_CODE pc ON e.FK_POST_CODE = pc.PC_ID
""")
employees = cursor.fetchall()

for employee in employees:
    roles = []
    if employee['agent_id']:
        agent_role = {
            "role": "Agent",
            "agent_id": employee['agent_id'],
            "capability_level": employee['CAPABILITY_LEVEL'],
            "tools": []
        }
        cursor.execute("SELECT * FROM TOOL WHERE FK_A_ID = %s", (employee['agent_id'],))
        tools = cursor.fetchall()
        for tool in tools:
            agent_role["tools"].append({
                "tool_id": tool['T_ID'],
                "description": tool['DESCRIPTION'],
                "amount": tool['AMOUNT']
            })
        roles.append(agent_role)

    if employee['analyst_id']:
        roles.append({
            "role": "Analyst",
            "analyst_id": employee['analyst_id'],
            "specialisation": employee['SPECIALISATION'],
            "years_of_experience": employee['YEARS_OF_EXPERIENCE']
        })

    assignments = []
    cursor.execute("SELECT * FROM ASSIGNED_TO WHERE ASS_E_ID = %s", (employee['E_ID'],))
    assignments_data = cursor.fetchall()
    for assignment in assignments_data:
        assignments.append({
            "branch_id": assignment['ASS_B_ID'],
            "since": convert_date_to_datetime(assignment['SINCE']),
            "till": convert_date_to_datetime(assignment['TILL'])
        })

    doc = {
        "employee_id": employee['E_ID'],
        "first_name": employee['FIRST_NAME'],
        "last_name": employee['LAST_NAME'],
        "email_address": employee['EMAIL_ADDRESS'],
        "street": employee['STREET'],
        "telephone_number": employee['TELEPHONE_NUMBER'],
        #"post_code": employee['FK_POST_CODE'],
        "post_code": {
            "pc_id": employee['PC_ID'],
            "post_code": employee['POST_CODE'],
            "city": employee['CITY']
        },
        "superior_id": employee['SUPERIOR_FS'],
        "roles": roles,
        "assignments": assignments
    }
    db.employees.insert_one(doc)

# Fetch and create branches data
cursor.execute("""
    SELECT B.*, FT.TYPE as facility_type, FT.CAPACITY, PC.PC_ID, PC.POST_CODE, PC.CITY as CITY 
    FROM BRANCH B 
    LEFT JOIN FACILITY_TYPE FT ON B.FK_TYPE=FT.FT_ID 
    LEFT JOIN POST_CODE PC ON B.FK_POST_CODE=PC.PC_ID
""")
branches = cursor.fetchall()
for branch in branches:
    doc = {
        "branch_id": branch['B_ID'],
        "name": branch['NAME'],
        "street": branch['STREET'],
        "post_code": {
            "pc_id": branch['PC_ID'],
            "post_code": branch['POST_CODE'],
            "city": branch['CITY']
        },
        #"post_code": branch['FK_POST_CODE'],
        "facility_type": {
            "type_id": branch['FK_TYPE'],
            "type": branch['facility_type'],
            "capacity": branch['CAPACITY']
        }
    }
    db.branches.insert_one(doc)

# Fetch and create missions data
cursor.execute("""
    SELECT m.M_ID, m.CODENAME, m.DESCRIPTION, m.M_DATE, m.ONGOING, m.STATUS, 
           s.S_ID as subject_id, s.FIRST_NAME as subject_first_name, s.LAST_NAME as subject_last_name, s.COI, 
           ep.P_ID as partner_id, ep.NAME as partner_name, ep.CONTACT, 
           GROUP_CONCAT(a.A_ID) as agent_ids
    FROM MISSIONLOG m
    JOIN SUBJECT s ON m.FK_S_ID = s.S_ID
    LEFT JOIN EXTERN_PARTNER ep ON m.FK_P_ID = ep.P_ID
    LEFT JOIN TAKES_ON t ON m.M_ID = t.FK_M_ID
    LEFT JOIN AGENT a ON t.FK_A_ID = a.A_ID
    GROUP BY m.M_ID
""")
missions = cursor.fetchall()

for mission in missions:
    agents = [{"agent_id": int(agent_id.strip()), "role": "Assigned"} for agent_id in mission['agent_ids'].split(',')] if mission['agent_ids'] else []
    doc = {
        "mission_id": mission['M_ID'],
        "codename": mission['CODENAME'],
        "description": mission['DESCRIPTION'],
        "date": convert_date_to_datetime(mission['M_DATE']),
        "ongoing": mission['ONGOING'],
        "status": mission['STATUS'],
        "subject": {
            "subject_id": mission['subject_id'],
            "first_name": mission['subject_first_name'],
            "last_name": mission['subject_last_name'],
            "coi": mission['COI']
        },
        "external_partner": {
            "partner_id": mission['partner_id'],
            "name": mission['partner_name'],
            "contact": mission['CONTACT']
        },
        "agents": agents
    }

    db.missionlogs.insert_one(doc)

# Create Indexes

db.employees.create_index("employee_id")
db.employees.create_index("first_name")
db.employees.create_index("last_name")

db.branches.create_index("branch_id")
db.branches.create_index("name")

db.missionlogs.create_index("mission_id")
db.missionlogs.create_index("agents.agent_id")
db.missionlogs.create_index("date")
db.missionlogs.create_index("status")


cursor.close()
mysql_conn.close()

print("Data migrated and indexes created successfully.")