#!/usr/bin/env bash

. $(dirname "$0")/func.sh

here=`dirname $0`

cd "$here/../"

current=$(latest_version)
next=$(next_version)

echo "creating release $next"

gh release create $next --title "$next" --generate-notes

git pull
