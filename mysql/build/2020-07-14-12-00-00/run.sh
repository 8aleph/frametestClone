#!/bin/sh

# Base root
cd /srv/build/2020-07-14-12-00-00

repeatretry() {
    count=0
    while ! eval "$1"; do
	if [ $count -gt 200 ]; then
		exit 255
	fi
        count=`expr $count + 1`
	sleep 2
    done
}

CRED_FILE='/datadb/__app/configs/admin.cnf'
COMMAND_MYSQL="mysql --defaults-extra-file=${CRED_FILE} -D${DB_SCHEMA}"

echo
echo "Initializing Example DB: ${DB_SCHEMA}"
echo "-------------------------------------"

repeatretry "mysql --defaults-extra-file=${CRED_FILE} -s -e \"DROP SCHEMA IF EXISTS ${DB_SCHEMA};\""
repeatretry "mysql --defaults-extra-file=${CRED_FILE} -s -e \"CREATE SCHEMA IF NOT EXISTS ${DB_SCHEMA} DEFAULT CHARACTER SET utf8mb4;\""
repeatretry "mysql --defaults-extra-file=${CRED_FILE} -s -e \"USE ${DB_SCHEMA};\""

echo "> Create schema"
repeatretry "${COMMAND_MYSQL} < model/example.sql";
echo

echo "> Load seed data"
eval "${COMMAND_MYSQL} < init/init.sql";
echo

echo "Initializing Complete"
echo "---------------------"
echo
