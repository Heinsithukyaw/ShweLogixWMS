# ShweLogixWMS Event-Driven Architecture - Deployment Guide

## 🎯 Quick Start

### 1. Start All Services

```bash
# Start Laravel API (if not running)
cd /workspace/ShweLogixWMS/wms-api
php artisan serve --host=0.0.0.0 --port=12000 &

# Start Queue Worker
php artisan queue:work --daemon &

# Start Scheduler (in production, use cron)
php artisan schedule:work &

# Start React Frontend
cd /workspace/ShweLogixWMS/wms-frontend-react
npm run dev -- --host 0.0.0.0 --port 12001 &
```

### 2. Set Authentication Token

Open browser and navigate to: `http://localhost:12001/set-token.html`
Click "Set Token" button to configure authentication.

### 3. Access Event Dashboard

Navigate to: `http://localhost:12001/system/event-monitoring`

## 🔧 Production Configuration

### Environment Variables (.env)

```env
# Event System Configuration
EVENT_MONITORING_ENABLED=true
EVENT_RETENTION_DAYS=30
EVENT_BATCH_SIZE=100
EVENT_CLEANUP_INTERVAL=daily

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shwelogix_wms
DB_USERNAME=root
DB_PASSWORD=

# API Configuration
API_RATE_LIMIT=60
API_THROTTLE_REQUESTS=1000
```

### Cron Job Setup

Add to crontab (`crontab -e`):

```bash
* * * * * cd /path/to/ShweLogixWMS/wms-api && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor Configuration (Queue Worker)

Create `/etc/supervisor/conf.d/shwelogix-worker.conf`:

```ini
[program:shwelogix-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/ShweLogixWMS/wms-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/shwelogix-worker.log
stopwaitsecs=3600
```

## 📊 API Endpoints Reference

### Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/v1/events/statistics` | Event statistics by type and time |
| GET | `/api/admin/v1/events/performance` | Performance metrics |
| GET | `/api/admin/v1/events/backlog` | Queue backlog status |
| GET | `/api/admin/v1/events/logs` | Event logs with pagination |
| GET | `/api/admin/v1/events/idempotency-statistics` | Idempotency statistics |
| GET | `/api/admin/v1/events/dashboard-summary` | Dashboard overview |

### Query Parameters

#### Statistics Endpoint
```
GET /api/admin/v1/events/statistics?timeframe=day&event_name=inventory.threshold.alert
```

#### Logs Endpoint
```
GET /api/admin/v1/events/logs?page=1&per_page=50&event_source=inventory_service
```

## 🔍 Monitoring & Maintenance

### Health Checks

```bash
# Check API health
curl -I http://localhost:12000/api/health

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Check queue status
php artisan queue:work --once

# Check Redis connection
redis-cli ping
```

### Log Monitoring

```bash
# Laravel logs
tail -f /path/to/ShweLogixWMS/wms-api/storage/logs/laravel.log

# Queue worker logs
tail -f /var/log/shwelogix-worker.log

# System logs
journalctl -u nginx -f
journalctl -u mysql -f
```

### Performance Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Database optimization
php artisan migrate --force
php artisan db:seed --class=EventSystemSeeder
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Authentication Errors
```bash
# Generate new access token
php artisan tinker --execute="
\$user = App\Models\User::find(1);
\$token = \$user->createToken('EventMonitoring')->accessToken;
echo 'Token: ' . \$token;
"
```

#### 2. Queue Not Processing
```bash
# Restart queue worker
php artisan queue:restart

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

#### 3. Database Connection Issues
```bash
# Test database connection
php artisan migrate:status

# Reset database (development only)
php artisan migrate:fresh --seed
```

#### 4. CORS Issues
Add to `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://localhost:12001'],
'allowed_headers' => ['*'],
```

### Debug Commands

```bash
# Enable debug mode
php artisan down
# Edit .env: APP_DEBUG=true
php artisan up

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check event system status
php /path/to/ShweLogixWMS/test_event_system.php
```

## 📈 Scaling Considerations

### Database Optimization
- Index frequently queried columns
- Partition large event tables by date
- Implement database read replicas

### Queue Scaling
- Use multiple queue workers
- Implement queue priorities
- Monitor queue depth

### Caching Strategy
- Redis for session storage
- Cache API responses
- Implement event result caching

### Load Balancing
- Multiple API server instances
- Database connection pooling
- CDN for static assets

## 🔐 Security Checklist

- [ ] HTTPS enabled in production
- [ ] Database credentials secured
- [ ] API rate limiting configured
- [ ] CORS properly configured
- [ ] Authentication tokens rotated regularly
- [ ] Logs monitored for suspicious activity
- [ ] Database backups automated
- [ ] Error reporting configured

## 📚 Additional Resources

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Passport Documentation](https://laravel.com/docs/passport)
- [React TypeScript Guide](https://react-typescript-cheatsheet.netlify.app/)
- [Recharts Documentation](https://recharts.org/)

## 🎉 Success Metrics

The event system is successfully deployed when:

1. ✅ All API endpoints return 200 status
2. ✅ Dashboard displays real-time data
3. ✅ Queue workers process events
4. ✅ Monitoring alerts are functional
5. ✅ Authentication works correctly
6. ✅ Performance metrics are collected
7. ✅ Idempotency prevents duplicates
8. ✅ Error handling works properly

**System Status**: 🟢 FULLY OPERATIONAL