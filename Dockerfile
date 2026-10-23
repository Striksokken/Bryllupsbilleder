FROM php:8.5.12-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .
COPY docker/local-gallery.conf /etc/apache2/conf-available/local-gallery.conf
COPY docker/php-local.ini /usr/local/etc/php/conf.d/local-gallery.ini

RUN sed -i '\|^Alias /icons/|d' /etc/apache2/mods-available/alias.conf \
    && a2enconf local-gallery

# The local Apache config deliberately ignores the production .htaccess
# (including its public-site redirect and IP restrictions).
RUN mkdir -p uploads \
    && chown -R www-data:www-data uploads

EXPOSE 80
