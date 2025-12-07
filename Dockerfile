# Sử dụng PHP 8 + Apache
FROM php:8.1-apache

# Copy toàn bộ mã nguồn vào thư mục web của Apache
COPY . /var/www/html/

# Mở port
EXPOSE 80

# Bật rewrite (nếu cần)
RUN a2enmod rewrite
