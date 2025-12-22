#!/bin/bash

# Quick Setup Script for License Service

set -e

echo "==================================="
echo "License Service - Quick Setup"
echo "==================================="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "Docker is not running. Please start Docker first."
    exit 1
fi

echo "Docker is running"
echo ""

# Step 1: Start services
echo "Step 1: Starting services..."
docker compose up -d

echo "Waiting for database to be ready..."
sleep 5

# Step 2: Initialize database
echo ""
echo "Step 2: Initializing database..."
docker exec -i mariadb mariadb -uroot -proot_password < database/schema.sql

# Step 3: Seed data
echo ""
echo "Step 3: Seeding sample data..."
docker exec php-app php /var/www/database/seed.php

# Step 4: Health check
echo ""
echo "Step 4: Health check..."
sleep 2
curl -s http://localhost:8080/health

echo ""
echo ""
echo "==================================="
echo "Setup Complete!"
echo "==================================="
echo ""
echo "Service is running at: http://localhost:8080"
echo ""
echo "Next steps:"
echo "  1. Check the seed output above for API keys"
echo "  2. Use curl or Postman to test the API endpoints"
echo "  3. See README.md for API documentation"
echo ""
echo "Useful commands:"
echo "  - View logs: docker compose logs -f web"
echo "  - Stop services: docker compose down"
echo "  - Restart: docker compose restart"
echo ""
