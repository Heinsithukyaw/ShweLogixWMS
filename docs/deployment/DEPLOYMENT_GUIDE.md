# ShweLogixWMS Deployment Guide

## 🚀 Production Deployment Guide

This guide provides comprehensive instructions for deploying ShweLogixWMS to production environments.

---

## 📋 Prerequisites

### System Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher (or MariaDB 10.5+)
- **Node.js**: 18.0 or higher
- **Redis**: 6.0 or higher
- **Composer**: Latest version
- **NPM**: Latest version

### Server Requirements
- **CPU**: 4+ cores recommended
- **RAM**: 8GB minimum, 16GB recommended
- **Storage**: 100GB+ SSD storage
- **Network**: Stable internet connection for external integrations

---

## 🏗️ Installation Steps

### 1. Environment Setup

#### Clone Repository
```bash
git clone https://github.com/Heinsithukyaw/ShweLogixWMS.git
cd ShweLogixWMS
```

#### Backend Setup (Laravel API)
```bash
cd wms-api

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database connection
# Edit .env file with your database credentials
```

#### Frontend Setup (React)
```bash
cd ../wms-frontend-react

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Configure API URL
# Edit .env file with your API endpoint
```

### 2. Database Configuration

#### Database Setup
```sql
-- Create database
CREATE DATABASE shwelogix_wms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional)
CREATE USER 'shwelogix_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON shwelogix_wms.* TO 'shwelogix_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Environment Variables
```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shwelogix_wms
DB_USERNAME=shwelogix_user
DB_PASSWORD=secure_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Application Configuration
APP_NAME="ShweLogix WMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# API Configuration
API_URL=https://your-domain.com/api
FRONTEND_URL=https://your-domain.com
```

### 3. Database Migration and Seeding

```bash
cd wms-api

# Run migrations
php artisan migrate --force

# Seed database with initial data
php artisan db:seed --force

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Queue System Setup

#### Configure Queue Workers
```bash
# Start queue workers
php artisan queue:work --daemon --sleep=3 --tries=3 --max-time=3600

# For production, use supervisor to manage queue workers
```

#### Supervisor Configuration
Create `/etc/supervisor/conf.d/shwelogix-wms.conf`:
```ini
[program:shwelogix-wms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/shwelogix-wms/wms-api/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/shwelogix-wms/storage/logs/worker.log
stopwaitsecs=3600
```

### 5. Web Server Configuration

#### Nginx Configuration
Create `/etc/nginx/sites-available/shwelogix-wms`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    # API Backend
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Frontend
    location / {
        root /path/to/shwelogix-wms/wms-frontend-react/dist;
        try_files $uri $uri/ /index.html;
        
        # Cache static assets
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
}
```

#### Apache Configuration
Create `.htaccess` in the API root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 6. SSL Certificate Setup

#### Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🔧 Configuration

### 1. Integration Configuration

#### External System Credentials
```env
# ERP Systems
SAP_CLIENT_ID=your_sap_client_id
SAP_CLIENT_SECRET=your_sap_client_secret
SAP_BASE_URL=https://your-sap-instance.com

ORACLE_CLIENT_ID=your_oracle_client_id
ORACLE_CLIENT_SECRET=your_oracle_client_secret
ORACLE_BASE_URL=https://your-oracle-instance.com

# E-commerce Platforms
SHOPIFY_API_KEY=your_shopify_api_key
SHOPIFY_API_SECRET=your_shopify_api_secret
SHOPIFY_WEBHOOK_SECRET=your_webhook_secret

# Shipping Carriers
FEDEX_CLIENT_ID=your_fedex_client_id
FEDEX_CLIENT_SECRET=your_fedex_client_secret
FEDEX_ACCOUNT_NUMBER=your_account_number

UPS_CLIENT_ID=your_ups_client_id
UPS_CLIENT_SECRET=your_ups_client_secret
UPS_ACCOUNT_NUMBER=your_account_number
```

### 2. Security Configuration

#### OAuth2 Configuration
```bash
# Generate OAuth2 keys
php artisan passport:install

# Create personal access client
php artisan passport:client --personal
```

#### File Permissions
```bash
# Set proper permissions
sudo chown -R www-data:www-data /path/to/shwelogix-wms
sudo chmod -R 755 /path/to/shwelogix-wms
sudo chmod -R 775 /path/to/shwelogix-wms/wms-api/storage
sudo chmod -R 775 /path/to/shwelogix-wms/wms-api/bootstrap/cache
```

### 3. Performance Configuration

#### Redis Configuration
```bash
# Install Redis
sudo apt install redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Key settings:
# maxmemory 2gb
# maxmemory-policy allkeys-lru
# save 900 1
# save 300 10
# save 60 10000
```

#### PHP Configuration
```ini
; /etc/php/8.2/fpm/php.ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
```

---

## 📊 Monitoring and Maintenance

### 1. Health Checks

#### Application Health
```bash
# Check application status
curl -H "Authorization: Bearer YOUR_TOKEN" https://your-domain.com/api/admin/v1/health

# Check queue status
php artisan queue:monitor

# Check event system
php artisan event:status
```

#### Database Health
```sql
-- Check database connections
SHOW PROCESSLIST;

-- Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'shwelogix_wms'
ORDER BY (data_length + index_length) DESC;
```

### 2. Logging and Monitoring

#### Log Configuration
```env
# Logging configuration
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_DAYS=30
```

#### Monitoring Setup
```bash
# Install monitoring tools
sudo apt install htop iotop nethogs

# Set up log rotation
sudo nano /etc/logrotate.d/shwelogix-wms

/path/to/shwelogix-wms/wms-api/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### 3. Backup Strategy

#### Database Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-shwelogix-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/database"
DB_NAME="shwelogix_wms"

# Create backup
mysqldump -u shwelogix_user -p'secure_password' $DB_NAME > $BACKUP_DIR/shwelogix_wms_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/shwelogix_wms_$DATE.sql

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

#### File Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-shwelogix-files.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/files"
SOURCE_DIR="/path/to/shwelogix-wms"

# Create backup
tar -czf $BACKUP_DIR/shwelogix_files_$DATE.tar.gz -C $SOURCE_DIR .

# Keep only last 7 days
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

### 4. Scheduled Tasks

#### Cron Jobs
```bash
# Edit crontab
crontab -e

# Add scheduled tasks
# Database backup (daily at 2 AM)
0 2 * * * /usr/local/bin/backup-shwelogix-db.sh

# File backup (daily at 3 AM)
0 3 * * * /usr/local/bin/backup-shwelogix-files.sh

# Laravel scheduler (every minute)
* * * * * cd /path/to/shwelogix-wms/wms-api && php artisan schedule:run >> /dev/null 2>&1

# Log cleanup (weekly)
0 4 * * 0 find /path/to/shwelogix-wms/wms-api/storage/logs -name "*.log" -mtime +30 -delete
```

---

## 🔒 Security Hardening

### 1. Firewall Configuration
```bash
# Configure UFW firewall
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw deny 22/tcp
```

### 2. Fail2ban Setup
```bash
# Install Fail2ban
sudo apt install fail2ban

# Configure for Laravel
sudo nano /etc/fail2ban/jail.local

[laravel]
enabled = true
port = http,https
filter = laravel
logpath = /path/to/shwelogix-wms/wms-api/storage/logs/laravel.log
maxretry = 3
bantime = 3600
```

### 3. Security Headers
```nginx
# Add to Nginx configuration
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

---

## 🚨 Troubleshooting

### Common Issues

#### 1. Queue Workers Not Processing
```bash
# Check queue status
php artisan queue:work --once

# Restart queue workers
sudo supervisorctl restart shwelogix-wms-worker:*
```

#### 2. Database Connection Issues
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();

# Check database configuration
php artisan config:show database
```

#### 3. File Permission Issues
```bash
# Fix permissions
sudo chown -R www-data:www-data /path/to/shwelogix-wms
sudo chmod -R 755 /path/to/shwelogix-wms
sudo chmod -R 775 /path/to/shwelogix-wms/wms-api/storage
```

#### 4. SSL Certificate Issues
```bash
# Test SSL configuration
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Renew Let's Encrypt certificate
sudo certbot renew --dry-run
```

### Performance Issues

#### 1. Slow Database Queries
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Check slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
```

#### 2. High Memory Usage
```bash
# Check memory usage
free -h
ps aux --sort=-%mem | head -10

# Optimize PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Adjust pool settings:
# pm.max_children = 50
# pm.start_servers = 5
# pm.min_spare_servers = 5
# pm.max_spare_servers = 35
```

---

## 📞 Support

### Contact Information
- **Technical Support**: support@shwelogix.com
- **Documentation**: https://docs.shwelogix.com
- **GitHub Issues**: https://github.com/Heinsithukyaw/ShweLogixWMS/issues

### Emergency Procedures
1. **System Down**: Check logs at `/path/to/shwelogix-wms/wms-api/storage/logs/`
2. **Database Issues**: Check MySQL error log at `/var/log/mysql/error.log`
3. **Queue Issues**: Restart queue workers with `sudo supervisorctl restart shwelogix-wms-worker:*`
4. **Web Server Issues**: Check Nginx/Apache logs at `/var/log/nginx/` or `/var/log/apache2/`

---

**Deployment Guide Version**: 1.0.0  
**Last Updated**: January 2025  
**Status**: ✅ Production Ready 