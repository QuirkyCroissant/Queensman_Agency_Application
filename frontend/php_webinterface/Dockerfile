FROM php:8.0-apache

# Update package list and install dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    git \
    python3 \
    python3-pip \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html/

# Copy composer.json and composer.lock (if exists)
COPY composer.json /var/www/html/
COPY composer.lock /var/www/html/

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install PHP dependencies
RUN composer install

# Copy the rest of the application files
COPY . /var/www/html/

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY Queensman_imse_Insert.py .
COPY requirements_refill.txt .
RUN pip install -r requirements_refill.txt

EXPOSE 80