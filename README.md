***
### Docker Terminal Workflow:

1. build Docker Containers without cache usage(so that changes for webfiles get loaded in immediately)

   ```fish
   docker compose build --no-cache
   ```

2. start the Docker Servers
   ```fish
   docker-compose up
   ```
***

### Create Relational Database:

- run the create/drop sql scripts onto the relational database server from repo directory
  - CREATE Table Schema:
    
    bash:
    ```shell
      docker exec -i mysqldb mysql -u root -p'Schikuta<3' mysql_queensmandb < relational/mysql_scripts/imse_project_mysql_create.sql
    ```
    
    fish shell:
    ```fish
    cat relational/mysql_scripts/imse_project_mysql_create.sql | docker exec -i mysqldb mysql -u root -p'Schikuta<3' mysql_queensmandb
    ```
  - DROP Table Schema:
    
    bash:
    ```shell
      docker exec -i mysqldb mysql -u root -p'Schikuta<3' mysql_queensmandb < relational/mysql_scripts/imse_project_mysql_drop.sql
    ```
    
    fish shell:
    ```fish
    cat relational/mysql_scripts/imse_project_mysql_drop.sql | docker exec -i mysqldb mysql -u root -p'Schikuta<3' mysql_queensmandb
    ```
***
### Insert Auto-Generated Database Data with Python Program

optional create virtual environment

1. Import all Dependancies
   ```fish
    pip install -r backend/python/requirements.txt 
   ```
2. run program:
   ```fish
    python3 backend/python/Queensman_imse_Insert.py
   ```

***
### Migrate Relational Schema from MySQL to MongoDB

1. install dependancies
   ```fish
    pip install -r non_relational/requirements.txt 
   ```
2. run migration program:
   ```fish
    python3 non_relational/mongo_insert.py 
   ```
3. connect to MongoShell to Check migration
   ```fish
    docker exec -it mongodb mongosh
   ```
4. Show Database and specific Container

   shows all databases on system
   ```fish
   show dbs
   ```
   access to our project database
   ```fish
   use queensmandb
   ```
   lists of all collections
   ```fish
   show collections
   ```
   shows first entries of a specified collection
   ```fish
   db.<specific_collection_name>.find().pretty()
   ```
   for example:
   ```fish
   db.employees.find().pretty()
   ```
   show specific Object corresponding to a id attribute
   ```fish
   db.<specific_collection_name>.findOne({ <collection_id_attribute_name>: <id_value> })
   ```
   for example:
   ```fish
   db.employees.findOne({ employee_id: 14 })
   ```