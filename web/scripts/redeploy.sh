#!/usr/bin/env bash

# Saves uncommitted changes and reset repo
git stash
git reset --hard

# Move to master branch
git checkout

# Pull latest commits from master
git pull

# Build
npm i
npm run build
