#!/bin/sh
-e
cd "$(dirname "${0}"))"

chown -R mysql:mysql /datadb/
rm -rf /datadb/*

runuser -u mysql mysqld -- --initialize --daemonize=false --skip-networking

cat > /tmp/init.sql <<EOF
# Remove anonymous users
DELETE FROM mysql.user WHERE User='';

# Disable remote root
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

# Set root password
ALTER USER 'root'@'localhost' IDENTIFIED BY '$ROOT_PASS';
flush privileges;

DELETE FROM mysql.db WHERE \`User\` = '$ADMIN_ID';
DELETE FROM mysql.user WHERE \`User\` = '$ADMIN_ID';
flush privileges;

CREATE USER '$ADMIN_ID'@'%' IDENTIFIED BY '$ADMIN_PASS';

# Admin
GRANT
    SELECT,
    INSERT,
    UPDATE,
    DELETE,
    EXECUTE,
    SHOW VIEW,
    CREATE,
    ALTER,
    REFERENCES,
    INDEX,
    CREATE VIEW,
    CREATE ROUTINE,
    ALTER ROUTINE,
    DROP,
    LOCK TABLES,
    CREATE TEMPORARY TABLES,
    FILE,
    SUPER,
    PROCESS,
    RELOAD
ON *.* TO '$ADMIN_ID'@'%';
flush privileges;

DELETE FROM mysql.db WHERE \`User\` = '$USER_ID';
DELETE FROM mysql.user WHERE \`User\` = '$USER_ID';
flush privileges;

CREATE USER '$USER_ID'@'%' IDENTIFIED BY '$USER_PASS';

# User
GRANT
    SELECT,
    INSERT,
    UPDATE,
    DELETE,
    SHOW VIEW,
    REFERENCES,
    INDEX,
    LOCK TABLES,
    CREATE TEMPORARY TABLES,
    EXECUTE
ON *.* TO '$USER_ID'@'%';
flush privileges;
EOF

runuser -u mysql mysqld -- --init-file=/tmp/init.sql --skip-networking &

mkdir -p /datadb/__app/configs/
cat > /datadb/__app/configs/admin.cnf <<EOF
[client]
user='$ADMIN_ID'
password='$ADMIN_PASS'
EOF

cd /srv
sh /srv/scripts/migrate.sh

kill `cat /datadb/tables/*.pid`
rm -f /tmp/init.sql
wait
