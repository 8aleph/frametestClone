#!/bin/bash
ls web/code/cache/container* 2> /dev/null | while read file; do
    if [ -f "${file}" ]; then
        init-docker-compose-run rm "$file"
    fi
done

