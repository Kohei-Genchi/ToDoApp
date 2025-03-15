#!/bin/bash

LOG_FILE="/var/www/storage/logs/scheduler.log"
mkdir -p "$(dirname "$LOG_FILE")"
touch "$LOG_FILE"
chmod 777 "$LOG_FILE"
echo "Laravel Scheduler started at $(date)" | tee -a "$LOG_FILE"

# Run the scheduler every minute
while true; do
    echo "Running scheduled tasks: $(date)" | tee -a "$LOG_FILE"
    /usr/local/bin/php /var/www/artisan schedule:run --verbose --no-interaction >> "$LOG_FILE" 2>&1
    echo "---------------------------------" | tee -a "$LOG_FILE"
    sleep 60
done
