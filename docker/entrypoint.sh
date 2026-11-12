#!/bin/sh
set -e

# Terapkan schema database (menunggu MySQL siap dengan retry).
php /var/www/html/database/migrate.php

exec "$@"
