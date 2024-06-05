FROM php:7.4-apache 

# installing mysqli extension that is necessary for database connection
RUN docker-php-ext-install mysqli

COPY . /var/www/html/

WORKDIR /var/www/html/

EXPOSE 80