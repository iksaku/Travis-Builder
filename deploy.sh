#!/usr/bin/env bash
echo "Preparing to deploy build..."
git init
git remote add origin https://$TOKEN@github.com/$TRAVIS_REPO_SLUG.git
git fetch origin
#git reset origin/$DEPLOY_BRANCH
git config user.name "LegendsOfMCPE-Bot"
git config user.email "LoM_Bot@travis.ci"
git add -A
echo "Commiting..."
git commit -m "New Build! Update: $TRAVIS_COMMIT"
echo "Uploading..."
git push --force --quiet origin HEAD:$DEPLOY_BRANCH
echo "Build successfully uploaded!"