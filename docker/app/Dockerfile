FROM dunglas/frankenphp:latest-php8.2-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    openssl-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

# Install MongoDB PHP extension with SSL support and other extensions
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Final stage
FROM base AS final

# Set working directory
WORKDIR /app

# Copy startup script
COPY docker/app/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose ports
EXPOSE 80 443 9003 2019

# Start both services using the startup script
CMD ["/usr/local/bin/start.sh"] 