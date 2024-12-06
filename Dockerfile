# Usar PHP como imagen base
FROM --platform=$TARGETPLATFORM php:8.1-fpm

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Forzar IPv4 para evitar problemas de conectividad
RUN echo "Acquire::ForceIPv4 \"true\";" > /etc/apt/apt.conf.d/99force-ipv4

# Instalar dependencias de sistema necesarias
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

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar todos los archivos del proyecto al contenedor
COPY . .

# Instalar dependencias de PHP
RUN composer install

# Establecer permisos durante la construcción del contenedor
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Asegurar permisos cada vez que el contenedor se ejecute
ENTRYPOINT ["sh", "-c", "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && exec php-fpm"]

# Exponer el puerto interno de PHP-FPM
EXPOSE 9000

# Comando por defecto para iniciar PHP-FPM
CMD ["php-fpm"]
