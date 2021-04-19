#!/bin/sh

# Base root
cd /srv/

CRED_FILE='/datadb/__app/configs/admin.cnf'
COMMAND_MYSQL="mysql --defaults-extra-file=${CRED_FILE}"

repeatretry() {
    count=0
    while ! mysql "$@"; do
        if [ $count -gt 200 ]; then
                exit 255
        fi
        count=`expr $count + 1`
        sleep 2
    done
}

# List migrations that have already been run
repeatretry --defaults-file=$CRED_FILE -s -e "CREATE SCHEMA IF NOT EXISTS ${DB_SCHEMA} DEFAULT CHARACTER SET utf8mb4;"
mysql --defaults-file=$CRED_FILE -D"${DB_SCHEMA}" -s -e "CREATE TABLE IF NOT EXISTS ex_migrations ( migration VARCHAR(255) PRIMARY KEY );"
mysql --defaults-file=$CRED_FILE -D"${DB_SCHEMA}" -s -e "SELECT migration FROM ex_migrations;" | sort > /tmp/db-migration

# List migrations that exist
ls /srv/build >> /tmp/db-migration
sort /tmp/db-migration > /tmp/db-migration-sorted

uniq -u /tmp/db-migration-sorted | while read migration; do
    echo "======== $migration ========"
    bash "/srv/build/$migration/run.sh"
    mysql --defaults-file=$CRED_FILE -D"${DB_SCHEMA}" -s -e "CREATE TABLE IF NOT EXISTS ex_migrations ( migration VARCHAR(255) PRIMARY KEY );"
    mysql --defaults-file=$CRED_FILE -D"${DB_SCHEMA}" -s -e "INSERT INTO ex_migrations (migration) VALUES ('$migration');" | tail -n+2 | sort > /tmp/db-migration
done
echo "======== Done ========"
