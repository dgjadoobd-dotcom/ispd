# Environment Configuration Guide

This document describes the environment configurations for the RADIUS Service Enhancement project.

## Overview

The project supports three environments:
1. **Development** - Local development environment
2. **Staging** - Pre-production testing environment  
3. **Production** - Live production environment

## Environment Files

### 1. Development Environment (`.env.development`)
- **Purpose**: Local development and testing
- **Database**: Local MySQL with test data
- **Debugging**: Full debugging enabled
- **Security**: Relaxed security for development
- **Ports**: 8080 (app), 3306 (db), 6379 (redis)

### 2. Staging Environment (`.env.staging`)
- **Purpose**: Pre-production testing
- **Database**: Staging database with realistic data
- **Debugging**: Limited debugging
- **Security**: Production-like security
- **Ports**: 8082 (app), 3307 (db), 6380 (redis)

### 3. Production Environment (`.env.production`)
- **Purpose**: Live production
- **Database**: Production database with replication
- **Debugging**: No debugging
- **Security**: Maximum security
- **Ports**: 80/443 (app), 3306 (db), 6379 (redis cluster)

## Docker Compose Files

### 1. Development (`docker-compose.dev.yml`)
- Single application container
- Development tools (MailHog, phpMyAdmin)
- Hot reload enabled
- Test data seeding

### 2. Staging (`docker-compose.staging.yml`)
- Single application container
- Monitoring stack (Prometheus, Grafana)
- Backup service
- Health checks

### 3. Production (`docker-compose.prod.yml`)
- Multiple application containers (load balanced)
- Redis cluster (3 nodes)
- Full monitoring stack (ELK, Prometheus, Grafana)
- Automated backups
- High availability configuration

## Deployment Scripts

### 1. Development Deployment
```bash
chmod +x deploy-dev.sh
./deploy-dev.sh
```

### 2. Staging Deployment
```bash
chmod +x deploy-staging.sh
./deploy-staging.sh
```

### 3. Production Deployment
```bash
chmod +x deploy-prod.sh
./deploy-prod.sh
```

## Environment Variables

### Required Variables
- `APP_KEY` - Application encryption key
- `DB_PASSWORD` - Database password
- `REDIS_PASSWORD` - Redis password
- `JWT_SECRET` - JWT secret key

### Database Configuration
- `DB_CONNECTION` - Database type (mysql/sqlite)
- `DB_HOST` - Database host
- `DB_PORT` - Database port
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username

### Redis Configuration
- `REDIS_HOST` - Redis host
- `REDIS_PORT` - Redis port
- `REDIS_PASSWORD` - Redis password
- `REDIS_DB` - Redis database number

### Application Configuration
- `APP_ENV` - Environment (development/staging/production)
- `APP_DEBUG` - Debug mode
- `APP_URL` - Application URL
- `APP_TIMEZONE` - Timezone

### Security Configuration
- `CORS_ALLOWED_ORIGINS` - Allowed CORS origins
- `RATE_LIMIT_ENABLED` - Rate limiting
- `SESSION_SECURE_COOKIE` - Secure cookies

## Health Checks

Each environment includes a health check endpoint:
- Development: `http://localhost:8080/health`
- Staging: `http://localhost:8082/health`
- Production: `http://localhost/health`

## Monitoring

### Staging & Production
- **Prometheus**: Metrics collection (`:9090`)
- **Grafana**: Dashboards (`:3001`)
- **ELK Stack**: Log aggregation (production only)

### Development
- **MailHog**: Email testing (`:8025`)
- **phpMyAdmin**: Database management (`:8081`)

## Backup Strategy

### Development
- Manual backups only
- No automated backup

### Staging
- Daily automated backups
- 7-day retention
- Local storage

### Production
- Daily automated backups
- 30-day retention
- S3 storage with encryption
- Cross-region replication

## Security Considerations

### Development
- Debug mode enabled
- Test credentials
- Local network only

### Staging
- Debug mode disabled
- Staging credentials
- Internal network access

### Production
- Debug mode disabled
- Production credentials
- SSL/TLS encryption
- Rate limiting
- Security headers
- WAF protection

## Troubleshooting

### Common Issues

1. **Database connection failed**
   - Check if MySQL is running
   - Verify credentials in .env file
   - Check network connectivity

2. **Redis connection failed**
   - Check if Redis is running
   - Verify password in .env file
   - Check port configuration

3. **Application not starting**
   - Check Docker logs: `docker-compose logs app`
   - Verify .env file exists
   - Check file permissions

4. **Health check failing**
   - Check individual service status
   - Verify port mappings
   - Check firewall rules

### Logs
- Application logs: `docker-compose logs app`
- Database logs: `docker-compose logs mysql`
- Redis logs: `docker-compose logs redis`
- Nginx logs: `docker-compose logs nginx`

## Maintenance

### Regular Tasks
1. Update dependencies
2. Review security patches
3. Monitor disk space
4. Check backup integrity
5. Review access logs

### Emergency Procedures
1. Database restore from backup
2. Service rollback
3. Security incident response
4. Disaster recovery

## Support

For environment-related issues:
1. Check this documentation
2. Review environment files
3. Check Docker logs
4. Contact system administrator