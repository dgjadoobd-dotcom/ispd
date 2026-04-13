# CI/CD Pipeline for RADIUS Service Enhancement

This directory contains GitHub Actions workflows for the RADIUS Service Enhancement project.

## Workflows

### 1. CI/CD Pipeline (ci-cd-pipeline.yml)
Main CI/CD pipeline that runs on every push and pull request.

**Jobs:**
- **test**: Runs the test suite with PHPUnit
- **security-scan**: Runs security scanning tools
- **build-and-test**: Builds Docker images and runs container tests
- **deploy-dev**: Deploys to development environment
- **deploy-staging**: Deploys to staging environment
- **deploy-prod**: Deploys to production environment

### 2. Security Scan (security-scan.yml)
Runs security scanning tools including:
- Trivy vulnerability scanning
- OWASP Dependency Check
- Snyk security scanning
- PHPStan static analysis
- PHP CS Fixer for code style

### 3. Code Quality (code-quality.yml)
Runs code quality checks including:
- PHPStan for static analysis
- PHP CS Fixer for code style
- PHP CodeSniffer
- PHPMD (PHP Mess Detector)
- PHP Copy/Paste Detector
- PHP Dead Code Detector

### 4. Monitoring (monitoring.yml)
Health checks and monitoring for all environments.

### 5. Deployment Workflows
- **deploy-development.yml**: Deploy to development
- **deploy-staging.yml**: Deploy to staging
- **deploy-production.yml**: Deploy to production

## Environment Variables

The following secrets need to be configured in GitHub Secrets:

### Required Secrets:
- `DOCKER_USERNAME`: Docker Hub username
- `DOCKER_PASSWORD`: Docker Hub password/token
- `SSH_PRIVATE_KEY`: SSH private key for deployment
- `SLACK_WEBHOOK_URL`: Slack webhook for notifications
- `SNYK_TOKEN`: Snyk API token
- `GITGUARDIAN_API_KEY`: GitGuardian API key

### Environment Variables:
- `DEV_SERVER_HOST`: Development server hostname/IP
- `STAGING_HOST`: Staging server hostname/IP
- `PRODUCTION_HOST`: Production server hostname/IP
- `SSH_USER`: SSH user for deployment
- `SSH_KEY`: SSH private key for deployment

## Deployment Environments

### Development
- **Branch**: `develop`
- **Deploy on**: Push to `develop` branch
- **URL**: http://dev.radius-service.local

### Staging
- **Branch**: `main` (on push)
- **Deploy on**: Push to `main` branch
- **URL**: https://staging.radius-service.com

### Production
- **Deploy on**: Release creation
- **URL**: https://radius-service.com

## Local Development

1. **Setup environment**:
   ```bash
   cp .env.example .env
   docker-compose up -d
   ```

2. **Run tests**:
   ```bash
   composer test
   ```

3. **Run security scan**:
   ```bash
   composer security-check
   ```

## Monitoring and Alerts

- **Health Checks**: Every 5 minutes
- **Alert Channels**: Slack, Email, SMS (PagerDuty)
- **Metrics**: Response time, error rates, uptime

## Security

- All secrets are stored in GitHub Secrets
- SSH keys are used for deployment
- All Docker images are scanned for vulnerabilities
- Regular security scanning with Trivy and Snyk

## Troubleshooting

1. **Build fails**: Check Docker build logs
2. **Deployment fails**: Check SSH key permissions
3. **Tests failing**: Check PHP version and dependencies
4. **Security scan failures**: Update dependencies

## Support

For issues with the CI/CD pipeline, contact the DevOps team.