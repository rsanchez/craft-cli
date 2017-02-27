#!/bin/sh

# Create a Craft CLI release
#
# Usage: ./github-release.sh <versionNumber>
#
# 1) Change dir to craft-cli repo
# 2) Bumps version number in src/Application.php
# 3) Commit, tag new version and push those changes/tags to origin
# 4) Build new craft.phar
# 5) Create new github release based on new tag
# 6) Upload craft.phar to release
# 7) Change dir to homebrew-craft-cli
# 8) Update craft-cli homebrew formula with latest version (version number, SHA1 hash, download url)
# 9) Commit and push changes to homebrew formula

TAG=$1

if [[ -z $CRAFT_CLI_HOMEBREW_DIR ]]; then
    CRAFT_CLI_HOMEBREW_DIR="../../homebrew-craft-cli"
fi

if [[ -z $(which github-release) ]]; then
    echo "\x1B[31mYou must first install github-release.\x1B[39m"
    exit 1
fi

if [[ -z $(which box) ]]; then
    echo "\x1B[31mYou must first install box.\x1B[39m"
    exit 1
fi

# check for a valid tag
if [[ -z $TAG ]]; then
    echo 'Usage: ./github-release.sh <tag-to-create>'
    exit 1
fi

# check if correct repo
if [[ $(git config --get remote.origin.url) != "git@github.com:rsanchez/craft-cli.git" ]]; then
    echo "\x1B[31mYou must be in the rsanchez/craft-cli repo.\x1B[39m"
    exit 1
fi

# go to root of repo
if [[ -n $(git rev-parse --show-cdup) ]]; then
    echo "\x1B[31mYou must be in root of the repo.\x1B[39m"
    exit 1
fi

# check if master branch
if [[ $(git rev-parse --abbrev-ref HEAD) != "master" ]]; then
    echo "\x1B[31mYou must be in the master branch.\x1B[39m"
    exit 1
fi

# check if there are any changes
if [[ -n $(git status -s -uno) ]]; then
    echo "\x1B[31mYou have uncommitted changes.\x1B[39m"
    exit 1
fi

# replace tag in text
perl -pi -w -e "s/const VERSION = '.*?';/const VERSION = '$TAG';/g;" src/Application.php

# commit version number changes
git add src/Application.php

git commit -m "Release $TAG"

# add new tag
git tag "$TAG"

# push changes
git push origin master

# and tags
git push --tags

# build the phar
box build

# create the github release
github-release release --user "rsanchez" --repo "craft-cli" --tag "$TAG"

# upload the phar to the release
github-release upload --user "rsanchez" --repo "craft-cli" --tag "$TAG" --name craft.phar --file craft.phar

# get sha hash of phar
SHA=$(shasum -a 256 craft.phar | cut -d ' ' -f 1)

cd $CRAFT_CLI_HOMEBREW_DIR

perl -pi -w -e "s/download\/.*?\/craft\.phar/download\/$TAG\/craft.phar/g;" craft-cli.rb
perl -pi -w -e "s/version '.*?'/version '$TAG'/g;" craft-cli.rb
perl -pi -w -e "s/sha1 '.*?'/sha256 '$SHA'/g;" craft-cli.rb
perl -pi -w -e "s/sha256 '.*?'/sha256 '$SHA'/g;" craft-cli.rb

git add craft-cli.rb

git commit -m "Release $TAG"

git push origin master
