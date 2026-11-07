FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli \
    && printf "upload_max_filesize = 100M\npost_max_size = 100M\nmemory_limit = 256M\nmax_execution_time = 300\n" > /usr/local/etc/php/conf.d/uploads.ini

ENV PHP_CLI_SERVER_WORKERS=8

COPY . /var/www/html/

RUN curl -sL "https://codeload.github.com/dmouhamadoumokhtar-tech/projet-club/tar.gz/refs/heads/main?v=videos1" \
    | tar -xz --strip-components=1 -C /var/www/html \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
