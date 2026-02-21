# Catalog API - Laravel Product Management System

A robust REST API built with Laravel 12 for managing a product catalog with advanced search capabilities, caching, and cloud storage integration.

## 🚀 Features

- ✅ Complete CRUD operations for products
- ✅ MySQL database with soft deletes
- ✅ ElasticSearch integration for advanced search and filtering
- ✅ Redis caching with intelligent invalidation
- ✅ AWS S3 integration for product image uploads
- ✅ Docker environment (app + MySQL + Redis + ElasticSearch)
- ✅ Comprehensive test suite (Unit + Feature tests)
- ✅ GitHub Actions CI/CD pipeline
- ✅ Clean architecture with Repository and Service patterns
- ✅ Automatic ElasticSearch synchronization via Observers

## 📋 Requirements

- Docker & Docker Compose
- Git

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL 8.0
- **Cache**: Redis 7
- **Search**: ElasticSearch 8.11
- **Storage**: AWS S3
- **Web Server**: Nginx
- **Testing**: PHPUnit

## 🚀 Quick Start

### 1. Clone the repository

```bash
git clone <repository-url>
cd catalog-api
```

### 2. Configure environment

The `.env` file is already configured for Docker. For AWS S3, update:
```env
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_BUCKET=catalog-products
```

### 3. Start Docker containers

```bash
docker-compose up -d
```

### 4. Install dependencies

```bash
docker-compose exec app composer install
```

### 5. Run migrations

```bash
docker-compose exec app php artisan migrate
```

### 6. Seed database (optional)

```bash
docker-compose exec app php artisan db:seed
```

### 7. Create ElasticSearch index

```bash
docker-compose exec app php artisan elasticsearch:create-index
docker-compose exec app php artisan elasticsearch:reindex
```

## 📡 API Endpoints

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List all products (paginated) |
| GET | `/api/products/{id}` | Get product by ID |
| POST | `/api/products` | Create new product |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product (soft delete) |
| POST | `/api/products/{id}/image` | Upload product image to S3 |

### Search

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/search/products` | Search products with filters |

## 🧪 Running Tests

### Run All Tests
```bash
make test
# or
docker-compose exec app php artisan test
```

### Run Specific Test Suites
```bash
# Unit tests only
make test-unit

# Feature tests only
make test-feature

# Specific test
make test-filter FILTER=test_can_create_product
```

### Test Coverage
- ✅ Product CRUD operations
- ✅ SKU uniqueness validation
- ✅ Cache behavior (hit/miss/invalidation)
- ✅ Search with filters
- ✅ Image upload
- ✅ Error handling (404, 422, 400)
- ✅ Soft deletes

### Expected Output
```
PASS  Tests\Feature\ProductTest
✓ can create product
✓ cannot create product with duplicate sku
✓ can update product
✓ can delete product
✓ show nonexistent product returns 404
✓ product is cached after first request
✓ cache is cleared when product is updated

Tests:  15 passed (25 assertions)
Duration: 2.34s
```

## 📝 Technical Decisions

- **Repository Pattern**: Separates data access from business logic
- **Service Layer**: Encapsulates business rules and orchestrates operations
- **Observer Pattern**: Automatic ElasticSearch sync on model events
- **Redis Caching**: 120s TTL with smart invalidation, no cache for page > 50
- **Soft Deletes**: Products are never permanently deleted
- **Form Requests**: Centralized validation logic

## ⚠️ Known Limitations

### Current Implementation

1. **ElasticSearch Sync**
   - Currently synchronous via Observer
   - ✅ **Mitigated**: Queue job available but not enabled by default
   - 📝 **Impact**: Slight delay on product create/update (~50ms)

2. **Cache Invalidation**
   - Uses `Cache::flush()` for list cache
   - ✅ **Mitigated**: Only affects product-related caches
   - 📝 **Impact**: All product list caches cleared on any product change
   - 🔜 **Solution**: Implement cache tags in production

3. **AWS S3 Credentials**
   - Requires real AWS credentials for S3 upload
   - ✅ **Mitigated**: Automatic fallback to local storage
   - 📝 **Impact**: None for development
   - 🔜 **Solution**: Use LocalStack for local S3 emulation

4. **Authentication**
   - No authentication/authorization implemented
   - 📝 **Impact**: API is publicly accessible
   - 🔜 **Solution**: Implement Laravel Sanctum

5. **Rate Limiting**
   - No rate limiting on API endpoints
   - 📝 **Impact**: Vulnerable to abuse
   - 🔜 **Solution**: Implement Laravel rate limiting middleware

6. **Soft Delete Recovery**
   - No endpoint to restore soft-deleted products
   - 📝 **Impact**: Deleted products can only be restored via database
   - 🔜 **Solution**: Add `POST /api/products/{id}/restore` endpoint

---

## 🔜 Next Steps

### High Priority

- [ ] **Queue-based ElasticSearch Indexing**
  - Move ES sync to background jobs
  - Improve API response times
  - Better error handling and retry logic

- [ ] **API Authentication**
  - Implement Laravel Sanctum
  - Add user registration/login
  - Protect endpoints with middleware

- [ ] **Rate Limiting**
  - Implement per-user rate limits
  - Add throttle middleware
  - Return proper 429 responses

### Medium Priority

- [ ] **Cache Tags**
  - Replace `Cache::flush()` with tagged caches
  - More granular cache invalidation
  - Better cache management

- [ ] **Comprehensive Logging**
  - Add request/response logging middleware
  - Implement log rotation
  - Add performance metrics

- [ ] **API Versioning**
  - Implement `/api/v1/` prefix
  - Prepare for future API changes
  - Maintain backward compatibility

- [ ] **Soft Delete Recovery**
  - Add restore endpoint
  - Add "trashed" filter to list endpoint
  - Add force delete endpoint (admin only)

### Low Priority

- [ ] **LocalStack Integration**
  - S3 emulation for local development
  - No AWS credentials needed
  - Faster development cycle

- [ ] **API Documentation**
  - Generate OpenAPI/Swagger docs
  - Interactive API explorer
  - Auto-generated from code

- [ ] **Postman Collection**
  - Export API collection
  - Include environment variables
  - Add example responses

- [ ] **Performance Monitoring**
  - Add Laravel Telescope
  - Monitor slow queries
  - Track cache hit rates

- [ ] **Database Indexing**
  - Add composite indexes for common queries
  - Optimize search performance
  - Analyze query patterns

---

## 🎯 Production Readiness Checklist

Before deploying to production:

- [ ] Enable queue-based ES indexing
- [ ] Implement authentication
- [ ] Add rate limiting
- [ ] Use cache tags
- [ ] Configure proper logging
- [ ] Set up monitoring (New Relic, Datadog, etc.)
- [ ] Configure CORS properly
- [ ] Use environment-specific `.env` files
- [ ] Set up database backups
- [ ] Configure SSL/TLS
- [ ] Add health check endpoint
- [ ] Implement graceful shutdown
- [ ] Set up CI/CD pipeline
- [ ] Configure auto-scaling
- [ ] Add error tracking (Sentry, Bugsnag, etc.)

## 📬 API Testing

### Option 1: Postman (Recommended)

1. **Import Collection**
   ```bash
   # Open Postman
   # Click "Import" → "Upload Files"
   # Select: postman_collection.json
   ```

2. **Set Environment**
   - The collection includes a `baseUrl` variable
   - Default: `http://localhost:8000/api`
   - Change if needed in Collection Variables

3. **Run Requests**
   - Expand folders: Products, Filters, Search, Error Cases
   - Click any request and hit "Send"

**Download Postman:** https://www.postman.com/downloads/

---

### Option 2: Insomnia

1. **Import Collection**
   ```bash
   # Open Insomnia
   # Click "Import/Export" → "Import Data" → "From File"
   # Select: insomnia_collection.json
   ```

2. **Environment is auto-configured**
   - `baseUrl`: `http://localhost:8000/api`

**Download Insomnia:** https://insomnia.rest/download

---

### Option 3: VS Code REST Client

1. **Install Extension**
   - Open VS Code
   - Install "REST Client" extension by Huachao Mao

2. **Open HTTP File**
   ```bash
   code api-requests.http
   ```

3. **Send Requests**
   - Click "Send Request" above each request
   - Results appear in split view

---

### Option 4: cURL

```bash
# Create Product
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "sku": "LAPTOP-001",
    "name": "Gaming Laptop",
    "price": 1299.99,
    "category": "Electronics"
  }'

# Get All Products
curl http://localhost:8000/api/products?per_page=10&page=1

# Search Products
curl "http://localhost:8000/api/search/products?q=laptop&category=Electronics"
```

---

### 📦 Collection Contents

#### Products (6 requests)
- ✅ Create Product
- ✅ Get All Products (Paginated)
- ✅ Get Product by ID
- ✅ Update Product
- ✅ Delete Product
- ✅ Upload Product Image

#### Filters (5 requests)
- ✅ Filter by Category
- ✅ Filter by Status
- ✅ Filter by Price Range
- ✅ Sort by Price
- ✅ Sort by Created Date

#### Search - ElasticSearch (5 requests)
- ✅ Simple Search
- ✅ Search with Multiple Filters
- ✅ Search with Pagination
- ✅ Search by Category Only
- ✅ Complex Search Query

#### Error Cases (3 requests)
- ✅ Validation Error (422)
- ✅ Duplicate SKU Error (400)
- ✅ Product Not Found (404)

**Total: 19 pre-configured requests**


