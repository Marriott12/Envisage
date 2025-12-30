# Envisage AI Platform - Docker Setup

## Quick Start

### Prerequisites
- Docker Desktop installed
- Docker Compose installed
- Git installed

### Start All Services
```bash
docker-compose up -d
```

### Stop All Services
```bash
docker-compose down
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f backend
docker-compose logs -f frontend
```

## Services

### Backend API (Laravel)
- **URL**: http://localhost:8000
- **Container**: envisage_backend
- **PHP**: 8.2-fpm
- **Features**: AI services, fraud detection, recommendations

### Frontend (Next.js)
- **URL**: http://localhost:3000
- **Container**: envisage_frontend
- **Node**: 18-alpine

### MySQL Database
- **Port**: 3306
- **Container**: envisage_mysql
- **Version**: 8.0
- **Database**: envisage
- **User**: envisage
- **Password**: (from .env)

### Redis Cache & Queue
- **Port**: 6379
- **Container**: envisage_redis
- **Version**: 7-alpine

### Queue Worker
- **Container**: envisage_queue
- **Purpose**: Process background jobs

### Nginx Web Server
- **Ports**: 80, 443
- **Container**: envisage_nginx
- **Purpose**: Reverse proxy and load balancer

## Initial Setup

### 1. Environment Configuration
```bash
# Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Update database credentials in backend/.env
DB_HOST=mysql
DB_DATABASE=envisage
DB_USERNAME=envisage
DB_PASSWORD=your_password

# Update Redis credentials
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
```

### 2. Build and Start
```bash
# Build all containers
docker-compose build

# Start all services
docker-compose up -d

# Check status
docker-compose ps
```

### 3. Initialize Application
```bash
# Install backend dependencies
docker-compose exec backend composer install

# Generate application key
docker-compose exec backend php artisan key:generate

# Run migrations
docker-compose exec backend php artisan migrate --force

# Seed database
docker-compose exec backend php artisan db:seed

# Clear and cache config
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache
```

### 4. Install Frontend Dependencies
```bash
docker-compose exec frontend npm install
```

## Development

### Access Containers
```bash
# Backend shell
docker-compose exec backend bash

# Frontend shell
docker-compose exec frontend sh

# MySQL shell
docker-compose exec mysql mysql -u envisage -p

# Redis CLI
docker-compose exec redis redis-cli
```

### Run Commands
```bash
# Backend
docker-compose exec backend php artisan system:status
docker-compose exec backend php artisan test

# Frontend
docker-compose exec frontend npm run dev
docker-compose exec frontend npm run build
```

### Database Management
```bash
# Create new migration
docker-compose exec backend php artisan make:migration create_example_table

# Run migrations
docker-compose exec backend php artisan migrate

# Rollback migrations
docker-compose exec backend php artisan migrate:rollback

# Fresh migration with seeding
docker-compose exec backend php artisan migrate:fresh --seed
```

### Queue Management
```bash
# View queue status
docker-compose exec backend php artisan queue:work

# Clear failed jobs
docker-compose exec backend php artisan queue:flush

# Restart queue workers
docker-compose restart queue
```

## Production Deployment

### Build for Production
```bash
# Build with production optimization
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build

# Start in production mode
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Production Checklist
- [ ] Update .env with production credentials
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Generate new APP_KEY
- [ ] Configure SSL certificates
- [ ] Set up database backups
- [ ] Configure monitoring
- [ ] Enable rate limiting
- [ ] Review security headers
- [ ] Test all services

## Troubleshooting

### Container Won't Start
```bash
# View logs
docker-compose logs backend

# Rebuild container
docker-compose build backend --no-cache
docker-compose up -d backend
```

### Permission Issues
```bash
# Fix storage permissions
docker-compose exec backend chown -R www-data:www-data storage
docker-compose exec backend chmod -R 775 storage
```

### Database Connection Issues
```bash
# Verify MySQL is running
docker-compose ps mysql

# Check connection from backend
docker-compose exec backend php artisan tinker
>>> DB::connection()->getPdo();
```

### Clear All Cache
```bash
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear
docker-compose exec backend php artisan view:clear
```

## Monitoring

### Health Checks
```bash
# API health
curl http://localhost:8000/health

# Detailed health
curl http://localhost:8000/health/detailed

# Metrics
curl http://localhost:8000/health/metrics
```

### Resource Usage
```bash
# Container stats
docker stats

# Specific container
docker stats envisage_backend
```

## Backup & Restore

### Database Backup
```bash
# Create backup
docker-compose exec mysql mysqldump -u envisage -p envisage > backup.sql

# Restore backup
docker-compose exec -T mysql mysql -u envisage -p envisage < backup.sql
```

### Volume Backup
```bash
# Backup MySQL data
docker run --rm -v envisage_mysql_data:/data -v $(pwd):/backup ubuntu tar czf /backup/mysql-backup.tar.gz /data

# Restore MySQL data
docker run --rm -v envisage_mysql_data:/data -v $(pwd):/backup ubuntu tar xzf /backup/mysql-backup.tar.gz -C /
```

## Scaling

### Horizontal Scaling
```bash
# Scale backend workers
docker-compose up -d --scale backend=3

# Scale queue workers
docker-compose up -d --scale queue=5
```

## Useful Commands

```bash
# Restart specific service
docker-compose restart backend

# View container processes
docker-compose top

# Remove all stopped containers
docker-compose rm

# Pull latest images
docker-compose pull

# Update and restart
docker-compose pull && docker-compose up -d
```
