FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libpq-dev zip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql intl zip gd opcache mbstring xml \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Set app to production during build
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

# Install dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application source
COPY . .

# Compile .env files for production (creates .env.local.php that locks APP_ENV=prod)
RUN composer dump-env prod

# Build Tailwind CSS
RUN php bin/console tailwind:build --minify || true

# Compile asset mapper (prevents runtime permission issues)
RUN php bin/console asset-map:compile --env=prod --no-debug || true

# Run post-install scripts (cache clear/warmup)
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod --no-debug || true

# Set Apache document root to Symfony public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Create var directory with proper permissions
RUN mkdir -p var/cache var/log public/uploads public/assets \
    && chown -R www-data:www-data var public/uploads public/assets \
    && chmod -R 775 var

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# OPcache config for production
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=256\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=0" > /usr/local/etc/php/conf.d/opcache-prod.ini

# Generate JWT keys at build time if not mounted
RUN mkdir -p config/jwt

EXPOSE 80

# Entrypoint: run migrations then start Apache
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
