#!/bin/bash

# Laravel Scheduler Simulator
# This script simulates cron job execution for Laravel scheduler

cd /workspace/ShweLogixWMS/wms-api

while true; do
    echo "$(date): Running Laravel scheduler..."
    php artisan schedule:run >> scheduler.log 2>&1
    sleep 60  # Run every minute
done