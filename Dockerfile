FROM php:8.2-fpm

# ដំឡើង System dependencies និង PHP extensions ចាំបាច់សម្រាប់ PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# ដំឡើង Composer (PHP Package Manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# កំណត់ Working Directory
WORKDIR /var/www

# ចម្លងកូដទាំងអស់ចូល
COPY . /var/www

EXPOSE 9000
CMD ["php-fpm"]