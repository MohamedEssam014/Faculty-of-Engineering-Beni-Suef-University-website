# استخدم نسخة PHP أكثر استقرارًا (Bullseye)
FROM php:8.1-apache-bullseye

# قم بتحديث قائمة الحزم ثم قم بتثبيت الإضافات المطلوبة (mysqli و pdo_mysql)
RUN apt-get update && docker-php-ext-install mysqli pdo_mysql

# انسخ ملف إعدادات الرفع المخصص إلى الحاوية
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
