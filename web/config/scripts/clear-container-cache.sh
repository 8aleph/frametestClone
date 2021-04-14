#!/bin/bash
ls | xargs echo | while read file; do
    if [ -f "${file}" ]; then
        initDockerComposeRun rm "$file"
    fi
done

