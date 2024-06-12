#!/bin/bash
# shell script which makes sure that the mysql server is perceptable
# for connections before running the data insertion script called by
# the dockerfile 
set -e

host="$1"
shift
cmd="$@"

until python -c "import mysql.connector; mysql.connector.connect(host='$host', user='root', password='Schikuta<3', database='mysql_queensmandb', port=3306)"; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

>&2 echo "MySQL is up - executing command"
exec $cmd