#!/usr/bin/env bash

# Root directory
cd $( dirname "${BASH_SOURCE[0]}" )
cd ..

vendor/bin/phpunit $*
