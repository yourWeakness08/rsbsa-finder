FROM --platform=linux/amd64 php:8.1.8

RUN apt-get update && apt-get install -y \
    curl zip unzip git gnupg libonig-dev libzip-dev

RUN docker-php-ext-install pdo pdo_mysql mbstring zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY wait-for-it.sh /app/wait-for-it.sh
RUN chmod +x /app/wait-for-it.sh

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]