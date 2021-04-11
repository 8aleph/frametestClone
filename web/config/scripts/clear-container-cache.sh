#!/bin/bash
ls web/code/cache/container* 2> /dev/null | while read file; do
    if [ -f "${file}" ]; then
        initDockerComposeRun rm "$file"
    fi
done

