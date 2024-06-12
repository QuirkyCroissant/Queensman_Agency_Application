#!/bin/sh

#Function to check MySQL connection
check_mysql() {
    until python -c "import mysql.connector; mysql.connector.connect(host='mysqldb', user='root', password='Schikuta<3', database='mysql_queensmandb', port=3306)"; do
        >&2 echo "MySQL is unavailable - sleeping"
        sleep 1
    done
}

#Wait for MySQL to be ready
check_mysql
mysql -h mysqldb -u root -p'Schikuta<3' mysql_queensmandb < /backend/relational/mysql_scripts/imse_project_mysql_drop.sql
mysql -h mysqldb -u root -p'Schikuta<3' mysql_queensmandb < /backend/relational/mysql_scripts/imse_project_mysql_create.sql

#Run the data insertion script
python3 Queensman_imse_Insert.py

#Keep the container running
tail -f /dev/null