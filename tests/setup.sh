#!/usr/bin/env bash

set -e
set -o pipefail

# Root directory
cd $( dirname "${BASH_SOURCE[0]}" )/..

# SFTP
docker run -d --rm --name acme_sftp -p 8022:22 atmoz/sftp acmephp:acmephp:::share

# pebble
MODE=${PEBBLE_MODE:-default}

docker run -d --rm --name acme_server --net host letsencrypt/pebble-challtestsrv pebble-challtestsrv -defaultIPv6 "" -defaultIPv4 127.0.0.1
docker run -d --rm --name acme_pebble --net host -e PEBBLE_VA_NOSLEEP=1 -e PEBBLE_WFE_NONCEREJECT=0 -e PEBBLE_ALTERNATE_ROOTS=1 -v $(pwd)/tests/Fixtures/pebble-config-$MODE.json:/test/config/pebble-config.json letsencrypt/pebble pebble -dnsserver 127.0.0.1:8053

# Wait for boot to be completed
docker run --rm --net host martin/wait -c localhost:14000,localhost:8022,localhost:8053,localhost:5002 -t 120
