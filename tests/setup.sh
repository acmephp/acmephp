#!/usr/bin/env bash

# Root directory
cd $( dirname "${BASH_SOURCE[0]}" )
cd ..

# SFTP
docker run -d --name acme_sftp -v `pwd`/tests/Cli/Fixtures/sftp:/home/acmephp/share -p 8022:22 atmoz/sftp acmephp:acmephp:1001

# Boulder
docker run -d --name acme_boulder --net host acmephp/testing-ca

# Wait for boot to be completed
docker run --rm --net host martin/wait -c localhost:4000,localhost:8022 -t 120
