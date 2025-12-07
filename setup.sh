#!/bin/bash

echo "========================================="
echo "E-Commerce GraphQL API Setup"
echo "========================================="
echo ""

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo "Error: Docker is not running. Please start Docker and try again."
        exit 1
    fi
}

# Function to wait for a service to be healthy
wait_for_service() {
    local service=$1
    local max_attempts=30
    local attempt=1

    echo "Waiting for $service to be healthy..."
    while [ $attempt -le $max_attempts ]; do
        if docker-compose ps | grep -q "$service.*healthy"; then
            echo "$service is healthy!"
            return 0
        fi
        echo "Attempt $attempt/$max_attempts: $service is not ready yet..."
        sleep 2
        ((attempt++))
    done

    echo "Warning: $service did not become healthy in time"
    return 1
}

# Check if Docker is running
check_docker

# Copy .env.example to .env if it doesn't exist
if [ ! -f "src/.env" ]; then
    echo "Creating .env file from .env.example..."
    cp src/.env.example src/.env
    echo ".env file created!"
else
    echo ".env file already exists, skipping..."
fi

# Build and start Docker containers
echo ""
echo "Building and starting Docker containers..."
docker-compose up -d --build

# Wait for essential services
wait_for_service "postgres"
wait_for_service "redis"
wait_for_service "elasticsearch"

echo ""
echo "Installing Composer dependencies..."
docker-compose exec -T php composer install --no-interaction --prefer-dist --optimize-autoloader

echo ""
echo "Generating application key..."
docker-compose exec -T php php artisan key:generate

echo ""
echo "Running database migrations..."
docker-compose exec -T php php artisan migrate --force

echo ""
echo "Setting up Laravel Passport..."
docker-compose exec -T php php artisan passport:keys --force
docker-compose exec -T php php artisan passport:client --personal --name="EcommerceAPI" --no-interaction || echo "Personal access client already exists"

echo ""
echo "Seeding database..."
docker-compose exec -T php php artisan db:seed --force

echo ""
echo "Clearing and caching configuration..."
docker-compose exec -T php php artisan config:clear
docker-compose exec -T php php artisan config:cache

echo ""
echo "========================================="
echo "Setup completed successfully!"
echo "========================================="
echo ""
echo "Application is running at: http://localhost:8080"
echo "GraphQL endpoint: http://localhost:8080/graphql"
echo ""
echo "Default test user:"
echo "Email: test@example.com"
echo "Password: password123"
echo ""
echo "Useful commands:"
echo "- View logs: docker-compose logs -f"
echo "- Stop containers: docker-compose down"
echo "- Restart containers: docker-compose restart"
echo ""
