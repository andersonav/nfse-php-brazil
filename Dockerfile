FROM php:8.3-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        nodejs \
        npm \
        libxml2-dev \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" dom curl mbstring soap \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/conf.d/*.ini /usr/local/etc/php/conf.d/

CMD ["bash"]
