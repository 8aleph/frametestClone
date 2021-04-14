#!/bin/bash
cd "$(dirname [pwd])"
chown www-data:www-data -R /code/cache/
exec "$@"

