# استخدم صورة PHP مبنية على bullseye (مستقرة)
FROM php:8.1-apache-bullseye

# تثبيت الأدوات اللازمة
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# نسخ ملفات المشروع
COPY ./src /var/www/html/

# ضبط الصلاحيات
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

