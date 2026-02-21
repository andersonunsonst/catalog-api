#!/bin/bash

echo "🚀 Setting up Catalog API..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Start Docker containers
echo "📦 Starting Docker containers..."
docker compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 15

# Install dependencies
echo "📥 Installing Composer dependencies..."
docker compose exec -T app composer install --no-interaction

# Generate application key
echo "🔑 Generating application key..."
docker compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker compose exec -T app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker compose exec -T app php artisan db:seed --force

# Create ElasticSearch index
echo "🔍 Creating ElasticSearch index..."
docker compose exec -T app php artisan elasticsearch:create-index

# Index products
echo "📊 Indexing products in ElasticSearch..."
docker compose exec -T app php artisan elasticsearch:reindex

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 API is available at: http://localhost:8000/api"
echo ""
echo "📡 Available endpoints:"
echo "  - GET    /api/products"
echo "  - POST   /api/products"
echo "  - GET    /api/products/{id}"
echo "  - PUT    /api/products/{id}"
echo "  - DELETE /api/products/{id}"
echo "  - POST   /api/products/{id}/image"
echo "  - GET    /api/search/products"
echo ""
echo "🧪 Run tests with: docker compose exec app php artisan test"
echo ""
echo "📝 Check README.md for more information"

