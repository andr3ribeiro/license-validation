# Multi-Tenant License Service

A production-ready multi-tenant license service built with pure PHP OO, no frameworks. This system enables multiple brands to provision, manage, and validate software licenses through a RESTful API.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Getting Started](#getting-started)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Design Decisions](#design-decisions)

## Features

### Implemented (Core Features)
- **Multi-tenant isolation**: Each brand operates independently with separate API keys
- **License key provisioning**: Brands can create license keys for customers
- **License creation**: Associate multiple product licenses with a single license key
- **License validation**: Products can validate licenses in real-time
- **License activation**: Track when licenses are first activated by products
- **License lifecycle management**: Suspend, reactivate, or cancel licenses
- **Brand management**: Register brands with separate provisioning and validation API keys
- **Product management**: Define products per brand
- **Seat management (User Story 3)**: End-user activation with seat limits
  - Per-license seat limit configuration
  - Instance-based activation tracking (domain/website)
  - Automatic seat limit enforcement
  - Prevention of duplicate seat consumption for same instance

### Designed (Future Extensions)
- Feature flags per license
- Usage tracking and analytics
- Webhook notifications
- License transfer/sharing
- Renewal and upgrade flows

## Architecture

### Clean Architecture Layers

```
┌─────────────────────────────────────────┐
│          HTTP Layer (Controllers)        │  ← API endpoints
├─────────────────────────────────────────┤
│       Application Layer (Services)       │  ← Business logic
├─────────────────────────────────────────┤
│        Domain Layer (Entities)           │  ← Core business rules
├─────────────────────────────────────────┤
│    Infrastructure Layer (Repositories)   │  ← Data persistence
└─────────────────────────────────────────┘
```

### Key Components

- **Domain Models**: `Brand`, `Product`, `LicenseKey`, `License`
- **Services**: `BrandService`, `LicenseKeyService`, `LicenseService`
- **Repositories**: Interface-based data access with MySQL implementation
- **Controllers**: `BrandController` (provisioning), `ProductController` (validation)
- **Router**: Simple pattern-based routing system

## Getting Started

### Prerequisites

- Docker & Docker Compose
- curl or Postman (for testing)

### Installation

Use the setup script:
```bash
./setup.sh
```

or

1. **Start the services**:
```bash
docker compose up -d
```

2. **Initialize the database**:
```bash
docker exec -i mariadb mariadb -uroot -proot_password < database/schema.sql
```

3. **Seed sample data**:
```bash
docker exec php-app php /var/www/html/database/seed.php
```

4. **Verify installation**:
```bash
curl http://localhost:8080/health
```

Expected response: `{"status":"ok"}`

### Project Structure

```
.
├── src/
│   ├── Domain/              # Domain models and business rules
│   │   ├── Entity.php
│   │   ├── Brand.php
│   │   ├── Product.php
│   │   ├── LicenseKey.php
│   │   ├── License.php
│   │   └── Exceptions.php
│   ├── Application/         # Business logic services
│   │   ├── BrandService.php
│   │   ├── LicenseKeyService.php
│   │   └── LicenseService.php
│   ├── Infrastructure/      # External dependencies
│   │   ├── Database.php
│   │   ├── IdGenerator.php
│   │   └── Repository/
│   │       ├── BrandRepository.php
│   │       ├── ProductRepository.php
│   │       ├── LicenseKeyRepository.php
│   │       ├── LicenseRepository.php
│   │       └── Impl/        # MySQL implementations
│   ├── Http/                # HTTP layer
│   │   ├── Controller.php
│   │   ├── Router.php
│   │   └── Controllers/
│   │       ├── BrandController.php
│   │       └── ProductController.php
│   ├── App.php              # Application bootstrap
│   ├── autoload.php         # PSR-4 autoloader
│   └── index.php            # Entry point
├── database/
│   ├── schema.sql           # Database schema
│   └── seed.php             # Sample data seeder
├── docker-compose.yml
├── nginx.conf
├── DESIGN.md                # Comprehensive system design
└── README.md
```

## API Documentation

### Authentication

All endpoints require authentication via Bearer token in the `Authorization` header:

```
Authorization: Bearer {api_key}
```

- **Brand Provisioning APIs** use the Brand API Key
- **Product Validation APIs** use the Product API Key

### Brand Provisioning APIs

#### 1. Create License Key

**Endpoint**: `POST /api/v1/brands/{brandId}/license-keys`

**Headers**:
```
Authorization: Bearer {brand_api_key}
Content-Type: application/json
```

**Request**:
```json
{
  "customer_email": "john@example.com"
}
```

**Response** (201):
```json
{
  "id": "uuid",
  "key": "RANK-2025-ABC123...",
  "customer_email": "john@example.com",
  "status": "active",
  "created_at": "2025-12-22T10:00:00+00:00"
}
```

#### 2. Get License Key

**Endpoint**: `GET /api/v1/brands/{brandId}/license-keys/{licenseKeyId}`

**Headers**:
```
Authorization: Bearer {brand_api_key}
```

**Response** (200):
```json
{
  "id": "uuid",
  "key": "RANK-2025-ABC123...",
  "customer_email": "john@example.com",
  "status": "active",
  "licenses": [
    {
      "id": "license-uuid",
      "product_id": "product-uuid",
      "product_name": "RankMath Pro",
      "status": "valid",
      "expires_at": "2026-12-22T00:00:00+00:00"
    }
  ],
  "created_at": "2025-12-22T10:00:00+00:00"
}
```

#### 3. Create License

**Endpoint**: `POST /api/v1/brands/{brandId}/licenses`

**Headers**:
```
Authorization: Bearer {brand_api_key}
Content-Type: application/json
```

**Request**:
```json
{
  "license_key_id": "key-uuid",
  "product_id": "product-uuid",
  "starts_at": "2025-12-22T00:00:00Z",
  "expires_at": "2026-12-22T00:00:00Z"
}
```

**Response** (201):
```json
{
  "id": "uuid",
  "license_key_id": "key-uuid",
  "product_id": "product-uuid",
  "status": "valid",
  "starts_at": "2025-12-22T00:00:00+00:00",
  "expires_at": "2026-12-22T00:00:00+00:00",
  "activated_at": null,
  "created_at": "2025-12-22T10:00:00+00:00"
}
```

#### 4. Update License Status

**Endpoint**: `PATCH /api/v1/brands/{brandId}/licenses/{licenseId}`

**Headers**:
```
Authorization: Bearer {brand_api_key}
Content-Type: application/json
```

**Request**:
```json
{
  "status": "suspended"
}
```

**Values**: `valid`, `suspended`, `cancelled`

**Response** (200):
```json
{
  "status": "suspended",
  "message": "License status updated"
}
```

### Product Validation APIs

#### 1. Validate License

**Endpoint**: `POST /api/v1/products/validate`

**Headers**:
```
Authorization: Bearer {product_api_key}
Content-Type: application/json
```

**Request**:
```json
{
  "license_key": "RANK-2025-ABC123...",
  "product_id": "product-uuid"
}
```

**Response** (200 - Valid):
```json
{
  "valid": true,
  "license_id": "uuid",
  "product_id": "product-uuid",
  "status": "valid",
  "expires_at": "2026-12-22T00:00:00+00:00",
  "activated_at": "2025-12-22T10:00:00+00:00"
}
```

**Response** (404 - Invalid):
```json
{
  "valid": false,
  "message": "License not found or invalid"
}
```

#### 2. Activate License

**Endpoint**: `POST /api/v1/products/activate`

**Headers**:
```
Authorization: Bearer {product_api_key}
Content-Type: application/json
```

**Request**:
```json
{
  "license_key": "RANK-2025-ABC123...",
  "product_id": "product-uuid",
  "activation_source": "plugin",
  "metadata": {}
}
```

**Response** (200):
```json
{
  "activated": true,
  "license_id": "uuid",
  "activated_at": "2025-12-22T10:00:00+00:00"
}
```

#### 3. Get Licenses by Key

**Endpoint**: `GET /api/v1/products/licenses/{licenseKey}`

**Headers**:
```
Authorization: Bearer {product_api_key}
```

**Response** (200):
```json
{
  "license_key": "RANK-2025-ABC123...",
  "licenses": [
    {
      "id": "license-uuid",
      "product_id": "product-uuid",
      "product_name": "RankMath Pro",
      "status": "valid",
      "expires_at": "2026-12-22T00:00:00+00:00"
    }
  ]
}
```

## Testing

### Using the Seeded Data

After running the seed script, you'll get output like this:

```
RankMath Brand:
  Brand ID: abc123...
  Brand API Key: sk_xyz...
  Product API Key: sk_pqr...
  License Key: RANK-2025-ABC123...
```

### Example Test Flow

1. **Validate a license** (simulating plugin checking license):
```bash
curl -X POST http://localhost:8080/api/v1/products/validate \
  -H "Authorization: Bearer {product_api_key}" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "RANK-2025-ABC123...",
    "product_id": "{product_id}"
  }'
```

2. **Create a new license key**:
```bash
curl -X POST http://localhost:8080/api/v1/brands/{brand_id}/license-keys \
  -H "Authorization: Bearer {brand_api_key}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "newcustomer@example.com"
  }'
```

3. **Add a license to that key**:
```bash
curl -X POST http://localhost:8080/api/v1/brands/{brand_id}/licenses \
  -H "Authorization: Bearer {brand_api_key}" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key_id": "{license_key_id}",
    "product_id": "{product_id}",
    "starts_at": "2025-12-22T00:00:00Z",
    "expires_at": "2026-12-22T00:00:00Z"
  }'
```

4. **Get license key details**:
```bash
curl http://localhost:8080/api/v1/brands/{brand_id}/license-keys/{license_key_id} \
  -H "Authorization: Bearer {brand_api_key}"
```

### User Story US1 Implementation

The system fully implements US1 as described:

**Scenario 1**: RankMath subscription
```bash
# Create license key
POST /api/v1/brands/{rankmath_id}/license-keys
{ "customer_email": "user@example.com" }
# Returns: { "key": "RANK-2025-ABC123..." }

# Create RankMath Pro license
POST /api/v1/brands/{rankmath_id}/licenses
{
  "license_key_id": "{key_id}",
  "product_id": "{rankmath_pro_id}",
  ...
}
```

**Scenario 2**: Add Content AI (same key)
```bash
# Create Content AI license using SAME license key
POST /api/v1/brands/{rankmath_id}/licenses
{
  "license_key_id": "{same_key_id}",  # Same key!
  "product_id": "{content_ai_id}",
  ...
}
```

**Scenario 3**: WP Rocket (different brand, different key)
```bash
# Create NEW license key for WP Rocket brand
POST /api/v1/brands/{wprocket_id}/license-keys
{ "customer_email": "user@example.com" }
# Returns: { "key": "WPRK-2025-XYZ789..." }  # Different key!

# Create WP Rocket license
POST /api/v1/brands/{wprocket_id}/licenses
{
  "license_key_id": "{new_key_id}",
  "product_id": "{wprocket_id}",
  ...
}
```

## Design Decisions

### 1. Pure PHP OO, No Frameworks

**Why**: Demonstrates understanding of core PHP concepts, OOP principles, and system design without framework abstractions.

**Trade-offs**:
- [V] Full control over architecture
- [V] No framework overhead
- [V] Educational value
- [X] More boilerplate code
- [X] Need manual implementation of routing, DI, etc.

### 2. Interface-Based Repositories

**Why**: Allows for easy testing and future persistence layer changes (PostgreSQL, MongoDB, etc.)

**Implementation**: Repository interfaces in `Infrastructure/Repository/`, MySQL implementations in `Impl/`

### 3. Separate API Keys for Provisioning vs Validation

**Why**:
- Least privilege principle
- Different rate limiting needs
- Different security requirements

**Trade-off**: Slightly more complex authentication, but better security

### 4. License Key Format: `{BRAND}-{YEAR}-{RANDOM}`

**Why**:
- Human-readable
- Brand-identifiable
- Timestamped for debugging
- Low collision risk

**Example**: `RANK-2025-A1B2C3D4E5F6`

### 5. UUID for All Primary Keys

**Why**:
- Distributed system friendly
- Privacy-preserving (not sequential)
- Unguessable

**Trade-off**: Larger than integers, but negligible with indexes

### 6. Soft Deletes for Brands

**Why**: Audit trail, compliance, data recovery

**Implementation**: `deleted_at` timestamp column

### 7. Service Layer Pattern

**Why**:
- Separates business logic from HTTP layer
- Enables reuse (CLI tools, background jobs)
- Easier testing

### 8. Domain-Driven Design

**Why**:
- Business logic in domain models (`License.suspend()`, `License.isValid()`)
- Rich domain models vs anemic data structures
- Clearer code intent

## Additional Documentation

See [DESIGN.md](DESIGN.md) for:
- Complete system architecture
- Data model details
- Future feature designs (usage metrics, webhooks, etc.)
- Scalability considerations
- Observability recommendations

## Security Considerations

1. **API Keys**: Stored in database, should be hashed in production
2. **HTTPS**: Use HTTPS in production (configured in nginx/reverse proxy)
3. **Rate Limiting**: Not implemented, should be added for production
4. **Input Validation**: Basic validation present, can be enhanced
5. **SQL Injection**: Prevented via prepared statements (PDO)

## License

This is a demonstration project for a technical assessment.

---
