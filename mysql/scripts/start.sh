#!/bin/sh

cd /
[ -d /datadb/tables/ ] || sh /srv/scripts/init.sh
exec runuser -u mysql mysqld
