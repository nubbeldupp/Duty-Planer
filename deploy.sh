#!/bin/bash

# Deployment Script for On-Call Duty Planner

# Exit on any error
set -e

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
APP_NAME="on-call-duty-planner"
DEPLOY_ENV="${1:-production}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Logging function
log() {
    echo -e "${GREEN}[DEPLOY] $1${NC}"
}

# Error handling
error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

# Pre-deployment checks
pre_deploy_checks() {
    log "Running pre-deployment checks..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
    fi

    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed"
    fi

    # Validate configuration files
    if [ ! -f "docker-compose.yml" ]; then
        error "docker-compose.yml is missing"
    fi

    log "Pre-deployment checks passed"
}

# Database migration
run_migrations() {
    log "Running database migrations..."
    docker-compose run --rm web php artisan migrate
}

# Build and push Docker images
build_images() {
    log "Building Docker images..."
    docker-compose build
}

# Deploy to environment
deploy() {
    log "Deploying to ${DEPLOY_ENV} environment..."
    
    # Stop existing containers
    docker-compose down

    # Start new containers
    docker-compose up -d

    # Run migrations
    run_migrations
}

# Backup current deployment
backup() {
    log "Creating deployment backup..."
    mkdir -p ./backups
    
    # Backup database
    docker-compose exec database pg_dump \
        -U app_user \
        -d on_call_duty_planner \
        > "./backups/backup_${TIMESTAMP}.sql"
}

# Rollback function
rollback() {
    log "Rolling back to previous deployment..."
    docker-compose down
    docker-compose up -d
}

# Main deployment workflow
main() {
    pre_deploy_checks
    backup
    build_images
    deploy

    log "Deployment completed successfully!"
}

# Execute main function
main

# Optional: Cleanup old backups (keep last 5)
find ./backups -type f -name "*.sql" -mtime +5 -delete
