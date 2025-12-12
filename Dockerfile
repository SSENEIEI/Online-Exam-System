FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

# Set permissions for logs directory
RUN mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/logs && \
    chmod 775 /var/www/html/logs

# Copy start script and make executable
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port 80
EXPOSE 80

# Use start script
CMD ["/usr/local/bin/start.sh"]
