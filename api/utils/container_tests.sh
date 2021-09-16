#!/usr/bin/env bash

vendor/bin/phinx migrate -e testing && vendor/bin/phpunit tests
