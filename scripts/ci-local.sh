#!/usr/bin/env bash
# The CI, run on this machine: what .github/workflows/ci.yml ran (php-cs-fixer
# and PHPUnit). There are no GitHub workflows: the account has no Actions
# billing. Run it before every push: scripts/ci-local.sh
# It uses the PHP on PATH; the workflow ran PHPUnit on PHP 8.0 and 8.1.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"
composer install --no-interaction
# php-cs-fixer 2 refuses to start on PHP above 8.0 without this; check-style
# is a dry run, so it changes no file.
PHP_CS_FIXER_IGNORE_ENV=1 composer check-style
# Coverage needs Xdebug or PCOV; without one, PHPUnit runs the tests alone.
if php -m | grep -qiE '^(xdebug|pcov)$'; then
  XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
else
  vendor/bin/phpunit
fi
echo "ci-local: passed"
