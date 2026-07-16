# =====================================================
# Laravel 12 - PHP 8.2 FPM
# =====================================================

FROM php:8.2-fpm


# =====================================================
# Set working directory
# =====================================================

WORKDIR /var/www


# =====================================================
# Install system dependencies
# =====================================================

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    nano \
    tzdata \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*


# =====================================================
# Set timezone (Germany)
# =====================================================

ENV TZ=Europe/Berlin

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone


# =====================================================
# Install PHP extensions required by Laravel
# =====================================================

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg


RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip


# =====================================================
# Install Composer
# =====================================================

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# =====================================================
# Copy PHP configuration
# =====================================================

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini


# =====================================================
# Copy entrypoint script
# =====================================================

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh


RUN chmod +x /usr/local/bin/entrypoint.sh


# =====================================================
# Copy Laravel project
# =====================================================

COPY . .


# =====================================================
# Laravel permissions
# =====================================================

RUN chown -R www-data:www-data storage bootstrap/cache


# =====================================================
# PHP-FPM Port
# =====================================================

EXPOSE 9000


# =====================================================
# Start container
# =====================================================

ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]
