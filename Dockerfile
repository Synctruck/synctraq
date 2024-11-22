# Use an official PHP runtime as a parent image
FROM --platform=$TARGETPLATFORM php:8.1-fpm
# Set working directory
WORKDIR /var/www/html

# Force IPv4 to avoid connectivity issues
RUN echo "Acquire::ForceIPv4 \"true\";" > /etc/apt/apt.conf.d/99force-ipv4

# Install dependencies with --fix-missing to handle missing packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libonig-dev \
    libgd-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
# Configure Git to avoid ownership warnings
RUN git config --global --add safe.directory /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy project files to container
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
