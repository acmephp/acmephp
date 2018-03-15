#!/usr/bin/env bash

# Root directory
cd $( dirname "${BASH_SOURCE[0]}" )
cd ..

# SFTP
docker run -d --name acme_sftp -p 8022:22 atmoz/sftp acmephp:acmephp:::share

# testing-ca
docker run -d --name acme_boulder --net host --add-host acmephp.com:127.0.0.1 acmephp/testing-ca:2.0

# Wait for boot to be completed
docker run --rm --net host martin/wait -c localhost:14000,localhost:8022 -t 120
