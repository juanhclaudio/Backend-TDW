#!/bin/bash
set -e

cd /home/wwwroot/aos
/usr/local/bin/composer update -o --no-interaction
php ./bin/doctrine.php -q dbal:run-sql "CREATE SCHEMA IF NOT EXISTS ${MYSQL_DATABASE}"
# ./bin/phpunit -c phpunit.xml.docker --coverage-text
/usr/local/bin/composer install --no-interaction

# set permissions for future files and folders
setfacl -dR -m u:"dev":rwX -m u:"dev":rwX /home/wwwroot/aos/var/

# set permissions on the existing files and folders
setfacl -R -m u:"dev":rwX -m u:"dev":rwX /home/wwwroot/aos/var/

exec "$@"