#!/bin/bash
chown www-data:www-data -R /code/cache
exec "$@"

