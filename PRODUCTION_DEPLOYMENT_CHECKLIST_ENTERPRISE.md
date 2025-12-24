# 🚀 Production Deployment Checklist

## Pre-Deployment (Development Environment)

### ✅ Code Quality
- [x] All migrations tested and run successfully
- [x] All models have fillable fields and relationships
- [x] Form requests validate all inputs
- [x] Services have error handling
- [x] Jobs have retry logic
- [x] Middleware registered in Kernel
- [x] Routes protected with auth/permissions
- [ ] Unit tests written (optional)
- [ ] Feature tests passing (optional)

### ✅ Configuration
- [x] `.env.example` updated with all variables
- [x] AI configuration complete in `config/ai.php`
- [x] Sentry config published
- [x] Permission config published
- [x] All sensitive data in .env, not hardcoded

### ✅ Database
- [x] All migrations created
- [x] Indexes added for performance
- [x] Seeders created for roles/permissions
- [x] Foreign keys properly set
- [x] Table existence checks in migrations

---

## Deployment Steps

### 1. Server Preparation

**Install Requirements:**
```bash
# PHP 7.4+ with extensions
php -m | grep -E "redis|pdo_mysql|mbstring|openssl|json"

# Composer
composer --version

# Redis Server
redis-cli ping

# MySQL/MariaDB
mysql --version
```

**Directory Permissions:**
```bash
cd /path/to/envisage/backend

# Storage and cache directories
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 2. Code Deployment

**Clone/Pull Code:**
```bash
git pull origin main
# OR
# Upload files via FTP/SFTP
```

**Install Dependencies:**
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Environment Configuration

**Copy Environment File:**
```bash
cp .env.example .env
nano .env  # or vim .env
```

**Critical Variables to Set:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_DATABASE=envisage_db
DB_USERNAME=envisage_user
DB_PASSWORD=SECURE_PASSWORD_HERE

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

OPENAI_API_KEY=sk-proj-...
SENTRY_LARAVEL_DSN=https://...@sentry.io/...

SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

**Generate Application Key:**
```bash
php artisan key:generate
```

### 4. Database Setup

**Run Migrations:**
```bash
php artisan migrate --force
```

**Seed Roles and Permissions:**
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

**Verify Database:**
```bash
php artisan tinker
>>> DB::table('roles')->count();  // Should return 6
>>> DB::table('permissions')->count();  // Should return 30
```

### 5. Caching & Optimization

**Clear All Caches:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Optimize for Production:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Optimize Composer Autoloader:**
```bash
composer dump-autoload --optimize
```

### 6. Queue Worker Setup

**Using Supervisor (Recommended):**

Create `/etc/supervisor/conf.d/envisage-worker.conf`:
```ini
[program:envisage-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/envisage/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/envisage/backend/storage/logs/worker.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start envisage-worker:*
```

**Verify Workers:**
```bash
sudo supervisorctl status envisage-worker:*
```

### 7. Web Server Configuration

**Nginx Configuration Example:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /path/to/envisage/backend/public;

    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    # API routes
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Rate limiting for AI endpoints
    location /api/ai/ {
        limit_req zone=ai_limit burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}

# Rate limit zone (add to http block in nginx.conf)
limit_req_zone $binary_remote_addr zone=ai_limit:10m rate=10r/s;
```

**Reload Nginx:**
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 8. Redis Configuration

**Secure Redis (redis.conf):**
```conf
# Bind to localhost only
bind 127.0.0.1

# Require password
requirepass YOUR_REDIS_PASSWORD

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""

# Enable persistence
save 900 1
save 300 10
save 60 10000

# Max memory
maxmemory 256mb
maxmemory-policy allkeys-lru
```

**Restart Redis:**
```bash
sudo systemctl restart redis
```

**Update .env:**
```env
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
```

### 9. Scheduler Setup

**Add to Crontab:**
```bash
crontab -e -u www-data
```

**Add Line:**
```cron
* * * * * cd /path/to/envisage/backend && php artisan schedule:run >> /dev/null 2>&1
```

### 10. Security Hardening

**File Permissions:**
```bash
# Make .env not readable by others
chmod 600 .env

# Secure storage
chmod -R 755 storage
chown -R www-data:www-data storage

# Prevent .git access
chmod 700 .git
```

**Additional Security:**
```bash
# Disable directory listing
echo "Options -Indexes" > public/.htaccess

# Block access to sensitive files
echo "deny from all" > .env.htaccess
```

---

## Post-Deployment Verification

### ✅ Application Health

**Test Homepage:**
```bash
curl https://yourdomain.com
# Should return 200 OK
```

**Test API Endpoint:**
```bash
curl https://yourdomain.com/api/health
# Or any public API route
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

### ✅ Database Connectivity

```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::table('users')->count();
```

### ✅ Redis Connectivity

```bash
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');  // Should return 'value'
```

### ✅ Queue System

**Dispatch Test Job:**
```bash
php artisan tinker
>>> App\Jobs\AI\GenerateRecommendationsJob::dispatch(1, [], 'neural', 10);
```

**Check Worker Logs:**
```bash
tail -f storage/logs/worker.log
```

### ✅ Authentication

**Create Test User:**
```bash
php artisan tinker
>>> $user = User::create([
    'name' => 'Test Admin',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
]);
>>> $user->assignRole('admin');
>>> $token = $user->createToken('test')->plainTextToken;
>>> echo $token;
```

**Test Protected Endpoint:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://yourdomain.com/api/user
```

### ✅ Rate Limiting

**Test Rate Limit:**
```bash
# Make 20+ rapid requests
for i in {1..25}; do
  curl https://yourdomain.com/api/recommendations
done
# Should see 429 Too Many Requests after limit
```

### ✅ Error Tracking

**Test Sentry Integration:**
```bash
php artisan tinker
>>> throw new Exception('Test Sentry Error');
```

**Check Sentry Dashboard:**
- Visit sentry.io
- Verify error appears

### ✅ Permissions System

**Test Permissions:**
```bash
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasPermissionTo('generate-content');
>>> $user->hasRole('admin');
```

---

## Monitoring Setup

### Daily Checks

**Check Queue:**
```bash
php artisan queue:failed
# Should be empty or minimal
```

**Check Costs:**
```sql
SELECT service, SUM(total_cost_usd) as daily_cost
FROM ai_costs
WHERE date = CURDATE()
GROUP BY service;
```

**Check Error Rate:**
```sql
SELECT 
    service,
    COUNT(*) as total,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as errors,
    (SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as error_rate
FROM ai_metrics
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY service;
```

### Weekly Reviews

**Budget Analysis:**
```sql
SELECT 
    DATE(created_at) as date,
    SUM(cost_usd) as daily_total
FROM ai_metrics
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

**A/B Test Results:**
```bash
php artisan tinker
>>> $abTest = app(\App\Services\ABTestService::class);
>>> $results = $abTest->getResults('your_experiment_name');
>>> print_r($results);
```

---

## Rollback Plan

**In Case of Issues:**

1. **Revert Code:**
```bash
git reset --hard HEAD~1
composer install --no-dev --optimize-autoloader
```

2. **Rollback Migrations:**
```bash
php artisan migrate:rollback --step=X
```

3. **Restore Database:**
```bash
mysql -u user -p database < backup.sql
```

4. **Clear All Caches:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
redis-cli FLUSHALL
```

5. **Restart Services:**
```bash
sudo systemctl restart php7.4-fpm
sudo systemctl restart nginx
sudo supervisorctl restart envisage-worker:*
```

---

## Performance Benchmarks

**Expected Performance:**
- API Response Time: < 200ms (without AI)
- AI Response Time: < 2000ms (with caching)
- Database Queries: < 50ms (with indexes)
- Queue Processing: 100+ jobs/minute
- Cache Hit Rate: > 80%
- Error Rate: < 1%

**Load Testing:**
```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test endpoint
ab -n 1000 -c 10 https://yourdomain.com/api/recommendations
```

---

## Emergency Contacts

**Key Personnel:**
- DevOps Lead: [email]
- Backend Developer: [email]
- Database Admin: [email]

**External Services:**
- OpenAI Support: platform.openai.com/support
- Sentry Support: sentry.io/support
- Redis Support: redis.io/support

---

## Completion Checklist

### Pre-Launch
- [ ] All code deployed
- [ ] Dependencies installed
- [ ] Environment configured
- [ ] Database migrated
- [ ] Roles/permissions seeded
- [ ] Caches optimized
- [ ] Queue workers running
- [ ] Web server configured
- [ ] SSL certificate installed
- [ ] Redis secured
- [ ] Scheduler configured

### Testing
- [ ] Homepage loads
- [ ] API endpoints respond
- [ ] Database queries work
- [ ] Redis caching works
- [ ] Queue jobs process
- [ ] Authentication works
- [ ] Rate limiting active
- [ ] Permissions enforced
- [ ] Sentry tracking errors

### Monitoring
- [ ] Log rotation configured
- [ ] Backup system active
- [ ] Monitoring alerts set
- [ ] Cost tracking enabled
- [ ] Performance metrics logged

### Documentation
- [ ] API documentation updated
- [ ] Admin credentials saved
- [ ] Configuration documented
- [ ] Runbook created
- [ ] Team trained

---

**🎉 Ready for Production!**

*Last Updated: December 24, 2025*
