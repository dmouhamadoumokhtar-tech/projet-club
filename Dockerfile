FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY . /var/www/html/

RUN curl -sL "https://codeload.github.com/dmouhamadoumokhtar-tech/projet-club/tar.gz/refs/heads/main?v=hd" \
    | tar -xz --strip-components=1 -C /var/www/html \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
