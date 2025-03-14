#!/bin/sh
set -e

# First arg is the command
if [ "$1" = "test" ]; then
  exec npm run test
elif [ "$1" = "test:watch" ]; then
  exec npm run test:watch
elif [ "$1" = "test:coverage" ]; then
  exec npm run test:coverage
else
  # Default: run the provided command
  exec "$@"
fi
