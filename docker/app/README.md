# Docker Configuration for Mumzworld Cart Service

This directory contains Docker configurations for both development and production environments.

## Docker Files

- `Dockerfile`: Development environment configuration
- `Dockerfile.production`: Production-optimized configuration
- `start.sh`: Startup script for development environment
- `start.production.sh`: Startup script for production environment

## Development Environment

The development environment includes:

- Hot reloading for code changes
- Xdebug for debugging
- Development-specific PHP settings
- Mounted volumes for real-time code editing

To start the development environment:

```bash
docker-compose up app
```

Access the development application at http://localhost:3000

## Production Environment

The production environment includes:

- Optimized PHP settings with OPcache enabled
- Multi-stage build for smaller image size
- Cached Laravel configurations
- No development dependencies
- Optimized autoloader

To start the production environment:

```bash
docker-compose up app-production
```

Access the production application at http://localhost:8000

## Key Differences

| Feature | Development | Production |
|---------|-------------|------------|
| PHP Settings | Development | Production with OPcache |
| Dependencies | All | Production only |
| Laravel Cache | Disabled | Enabled |
| File System | Mounted volumes | Copied files |
| Horizon | Local environment | Production environment |
| Octane Workers | Default | Optimized |

## Building Images Separately

To build the development image:

```bash
docker build -t mumzworld-cart-service:dev -f docker/app/Dockerfile .
```

To build the production image:

```bash
docker build -t mumzworld-cart-service:prod -f docker/app/Dockerfile.production .
```

## Environment Variables

Both environments use the same environment variables from your `.env` file, but the production environment sets `APP_ENV=production` and `HORIZON_ENVIRONMENT=production`. 

#######