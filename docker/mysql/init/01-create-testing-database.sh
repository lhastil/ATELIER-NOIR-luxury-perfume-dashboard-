# Runs once, when the MySQL data volume is first initialised.
# Creates the separate database used by `composer test:mysql`, so test runs
# never touch development data. Sourced by the image entrypoint, which
# provides docker_process_sql and the MYSQL_* variables. Keep this file
# non-executable: executable scripts are run, not sourced, and would fail.
docker_process_sql --database=mysql <<-EOSQL
	CREATE DATABASE IF NOT EXISTS \`atelier_noir_testing\`;
	GRANT ALL PRIVILEGES ON \`atelier\\_noir\\_testing\`.* TO '${MYSQL_USER}'@'%';
EOSQL
