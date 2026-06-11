FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mysqli mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-webgis.conf /etc/apache2/conf-available/webgis.conf
RUN a2enconf webgis

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/WebgisPovertyMapping/tmp \
    && chown -R www-data:www-data /var/www/html/WebgisPovertyMapping/tmp
