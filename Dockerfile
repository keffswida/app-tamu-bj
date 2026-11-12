FROM php:8.3-apache

ENV TZ=Asia/Jakarta

# pdo_mysql: driver database; mbstring: validasi panjang teks Unicode.
# rewrite: routing front controller (pola per-directory).
RUN set -eux; \
    a2enmod rewrite; \
    docker-php-ext-install pdo_mysql mbstring

COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint-app.sh

COPY --chown=www-data:www-data . /var/www/html

RUN chmod +x /usr/local/bin/docker-entrypoint-app.sh

WORKDIR /var/www/html

# Migrasi schema dulu, baru jalankan Apache.
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-app.sh"]
CMD ["apache2-foreground"]
