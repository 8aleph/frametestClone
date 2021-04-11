#!/bin/bash
cd "$(dirname "${0}")"
chown www-data:www-data -R /code/cache/
exec "$@"

