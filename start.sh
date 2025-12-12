#!/bin/bash

# Run database migration
echo "Running database auto-migration..."
php /var/www/html/database/auto_migrate.php

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
