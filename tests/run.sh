#!/usr/bin/env bash

docker run -d --name boulder --net host acmephp/testing-ca
docker run --rm --net host martin/wait -c localhost:4000 -t 120

vendor/bin/phpunit
