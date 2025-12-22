# Multi-Tenant License Service - System Design Document

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Data Model](#data-model)
4. [API Design](#api-design)
5. [Core Concepts](#core-concepts)
6. [Implementation Notes](#implementation-notes)
7. [Future Extensibility](#future-extensibility)

---

## System Overview

### Purpose
A multi-tenant License Service that acts as the single source of truth for licenses and entitlements across multiple brands. The system enables brands to provision, manage, and validate licenses for their customers while maintaining clear boundaries between different brands (tenants).

### Key Principles
1. **Multi-Tenancy**: Isolated namespaces per brand with shared infrastructure
2. **Scalability**: Designed to handle multiple licenses and concurrent requests
3. **Auditability**: All license operations are trackable for compliance
4. **Extensibility**: Support for future features (feature flags, usage metrics)
5. **Observability**: Rich logging and metrics for operational monitoring

---

## Architecture

### High-Level Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    API Layer (Controllers)                   │
│  Brand APIs (provisioning) | Product APIs (validation)       │
└────────────────┬────────────────────────────┬────────────────┘
                 │                            │
┌────────────────▼────────┐     ┌────────────▼───────────────┐
│   License Service        │     │  Brand Service              │
│ - License CRUD           │     │ - Brand registration        │
│ - License status mgmt    │     │ - Tenant isolation          │
│ - Entitlement mgmt       │     │ - API key management        │
└────────────────┬─────────┘     └────────────┬────────────────┘
                 │                            │
┌────────────────▼──────────────────────────┬▼───────────────┐
│              Repository Layer              │                │
│ - LicenseRepository                        │                │
│ - LicenseKeyRepository                     │                │
│ - ProductRepository                        │                │
└────────────────┬───────────────────────────┴────────────────┘
                 │
        ┌────────▼───────┐
        │   Database      │
        │ - Licenses      │
        │ - License Keys  │
        │ - Products      │
        │ - Brands        │
        │ - Audit Log     │
        └─────────────────┘
```

### Layers

#### 1. **API Layer (Controllers)**
- Handles HTTP requests/responses
- Input validation
- Request authentication/authorization
- Response serialization

#### 2. **Service Layer**
- Business logic implementation
- Orchestration of domain objects
- Transaction management
- License lifecycle management

#### 3. **Domain Model Layer**
- Pure domain objects (License, LicenseKey, Brand, Product, etc.)
- Business rules encapsulation
- Value Objects for domain concepts

#### 4. **Repository Layer**
- Data access abstraction
- Query building and optimization
- Entity persistence

#### 5. **Infrastructure Layer**
- Database connections
- Logging and observability
- Configuration management

---

## Data Model

### Entities and Relationships

#### 1. **Brand**
Represents a tenant in the system.

```
Brand
├── id: UUID
├── name: string (e.g., "RankMath", "WP Rocket")
├── slug: string (unique, e.g., "rankmath", "wp-rocket")
├── api_key_brand: string (for brand provisioning APIs)
├── api_key_product: string (for product APIs)
├── status: enum (active, suspended, deleted)
├── created_at: datetime
├── updated_at: datetime
└── deleted_at: datetime (soft delete)
```

#### 2. **Product**
Represents a product that can be licensed.

```
Product
├── id: UUID
├── brand_id: UUID (FK → Brand)
├── name: string (e.g., "RankMath Pro", "Content AI")
├── slug: string (unique per brand, e.g., "rankmath-pro")
├── description: string
├── status: enum (active, inactive)
├── created_at: datetime
├── updated_at: datetime
└── index: (brand_id, slug)
```

#### 3. **LicenseKey**
Represents a unique key given to customers. One key may unlock multiple licenses.

```
LicenseKey
├── id: UUID
├── brand_id: UUID (FK → Brand)
├── customer_email: string
├── key: string (unique, e.g., "RANK-2025-XXXXX...")
├── status: enum (active, inactive, cancelled)
├── created_at: datetime
├── updated_at: datetime
├── created_by_brand_id: UUID (which brand provisioned it)
└── indexes: (brand_id, customer_email), (key)
```

**Design Decision**: Although LicenseKey has a brand_id, it tracks which brand created it. This allows:
- RankMath to create multiple keys for the same customer (future multi-product scenarios)
- Clear audit trail of provisioning
- Support for brand partnerships

#### 4. **License**
Represents the actual entitlement to a product.

```
License
├── id: UUID
├── license_key_id: UUID (FK → LicenseKey)
├── product_id: UUID (FK → Product)
├── status: enum (valid, suspended, cancelled, expired)
├── starts_at: datetime
├── expires_at: datetime
├── activated_at: datetime (nullable - when product activated the key)
├── created_at: datetime
├── updated_at: datetime
└── indexes: (license_key_id), (product_id), (license_key_id, product_id)
```

#### 5. **LicenseActivation** (Optional - for observability)
Tracks activation events for auditing.

```
LicenseActivation
├── id: UUID
├── license_id: UUID (FK → License)
├── activated_at: datetime
├── user_agent: string
├── ip_address: string
├── activation_source: string (e.g., "plugin", "web")
└── metadata: JSON
```

#### 6. **AuditLog** (For compliance and debugging)
```
AuditLog
├── id: UUID
├── entity_type: string (License, LicenseKey, etc.)
├── entity_id: UUID
├── action: string (created, updated, deleted, activated)
├── brand_id: UUID
├── actor_type: string (brand, system)
├── old_values: JSON
├── new_values: JSON
├── created_at: datetime
└── indexes: (entity_type, entity_id), (brand_id, created_at)
```

### SQL Schema

```sql
-- Brands (Tenants)
CREATE TABLE brands (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    api_key_brand VARCHAR(255) NOT NULL UNIQUE,
    api_key_product VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_slug (slug),
    INDEX idx_api_key_brand (api_key_brand)
);

-- Products
CREATE TABLE products (
    id VARCHAR(36) PRIMARY KEY,
    brand_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    UNIQUE KEY unique_brand_product_slug (brand_id, slug),
    INDEX idx_brand_id (brand_id)
);

-- License Keys
CREATE TABLE license_keys (
    id VARCHAR(36) PRIMARY KEY,
    brand_id VARCHAR(36) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    key VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
    created_by_brand_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (created_by_brand_id) REFERENCES brands(id),
    INDEX idx_brand_customer (brand_id, customer_email),
    INDEX idx_key (key)
);

-- Licenses
CREATE TABLE licenses (
    id VARCHAR(36) PRIMARY KEY,
    license_key_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    status ENUM('valid', 'suspended', 'cancelled', 'expired') DEFAULT 'valid',
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    activated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_key_id) REFERENCES license_keys(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_key_product (license_key_id, product_id),
    INDEX idx_license_key_id (license_key_id),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);

-- License Activations (Optional)
CREATE TABLE license_activations (
    id VARCHAR(36) PRIMARY KEY,
    license_id VARCHAR(36) NOT NULL,
    activated_at TIMESTAMP NOT NULL,
    user_agent VARCHAR(500),
    ip_address VARCHAR(45),
    activation_source VARCHAR(100),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id),
    INDEX idx_license_id (license_id),
    INDEX idx_activated_at (activated_at)
);

-- Audit Log
CREATE TABLE audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(36) NOT NULL,
    action VARCHAR(50) NOT NULL,
    brand_id VARCHAR(36),
    actor_type VARCHAR(50),
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_brand_created (brand_id, created_at)
);
```

---

## API Design

### Authentication
All API endpoints require brand authentication via API key passed in `Authorization: Bearer {api_key}` header.

### Brand Provisioning APIs
**Base URL**: `/api/v1/brands/{brand_id}`
**Authentication**: Brand API Key

#### 1. Create License Key
```
POST /api/v1/brands/{brand_id}/license-keys
Authorization: Bearer {api_key_brand}
Content-Type: application/json

{
  "customer_email": "john@example.com"
}

Response (201):
{
  "id": "uuid",
  "key": "RANK-2025-ABC123...",
  "customer_email": "john@example.com",
  "status": "active",
  "created_at": "2025-12-22T10:00:00Z"
}
```

#### 2. Create License
```
POST /api/v1/brands/{brand_id}/licenses
Authorization: Bearer {api_key_brand}
Content-Type: application/json

{
  "license_key_id": "key-uuid",
  "product_id": "product-uuid",
  "starts_at": "2025-12-22T00:00:00Z",
  "expires_at": "2026-12-22T00:00:00Z"
}

Response (201):
{
  "id": "uuid",
  "license_key_id": "key-uuid",
  "product_id": "product-uuid",
  "status": "valid",
  "starts_at": "2025-12-22T00:00:00Z",
  "expires_at": "2026-12-22T00:00:00Z",
  "activated_at": null,
  "created_at": "2025-12-22T10:00:00Z"
}
```

#### 3. Get License Key (with associated licenses)
```
GET /api/v1/brands/{brand_id}/license-keys/{license_key_id}
Authorization: Bearer {api_key_brand}

Response (200):
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
      "expires_at": "2026-12-22T00:00:00Z"
    }
  ],
  "created_at": "2025-12-22T10:00:00Z"
}
```

#### 4. Suspend/Reactivate License
```
PATCH /api/v1/brands/{brand_id}/licenses/{license_id}
Authorization: Bearer {api_key_brand}
Content-Type: application/json

{
  "status": "suspended"  // or "valid"
}

Response (200): Updated license object
```

### Product-Facing APIs
**Base URL**: `/api/v1/products`
**Authentication**: Product API Key

#### 1. Validate License Key
```
POST /api/v1/products/validate
Authorization: Bearer {api_key_product}
Content-Type: application/json

{
  "license_key": "RANK-2025-ABC123...",
  "product_id": "product-uuid"
}

Response (200):
{
  "valid": true,
  "license_id": "uuid",
  "product_id": "product-uuid",
  "status": "valid",
  "expires_at": "2026-12-22T00:00:00Z",
  "activated_at": "2025-12-22T10:00:00Z"
}

Response (404/403):
{
  "valid": false,
  "message": "License not found or invalid"
}
```

#### 2. Activate License
```
POST /api/v1/products/activate
Authorization: Bearer {api_key_product}
Content-Type: application/json

{
  "license_key": "RANK-2025-ABC123...",
  "product_id": "product-uuid",
  "activation_source": "plugin",
  "metadata": {}
}

Response (200):
{
  "activated": true,
  "license_id": "uuid",
  "activated_at": "2025-12-22T10:00:00Z"
}
```

---

## Core Concepts

### License Lifecycle

```
Creation → Valid → [Suspended ↔ Valid] → Expired/Cancelled
                   ↓
                 Cancelled
```

- **Valid**: Usable by customer, within validity period
- **Suspended**: Temporarily deactivated (e.g., payment issue)
- **Cancelled**: Permanently revoked
- **Expired**: Reached expiration date (automatic transition)

### Multi-Brand Scenario

**Example: RankMath + Content AI (same key) + WP Rocket (different key)**

```
Brand: RankMath
├── Product: RankMath Pro
├── Product: Content AI
│
└── License Key: RANK-2025-ABC123
    ├── License 1: RankMath Pro (expires 2026-12-22)
    └── License 2: Content AI (expires 2026-12-22)

Brand: WP Rocket
├── Product: WP Rocket
│
└── License Key: ROCKET-2025-XYZ789
    └── License 1: WP Rocket (expires 2026-12-22)
```

**Why separate keys for different brands?**
- Clear separation of concerns
- Easier license revocation and management
- Better audit trails
- Prevents cross-brand exposure

---

## Implementation Notes

### Design Decisions

#### 1. **Key Generation**
- Format: `{BRAND_ACRONYM}-{YEAR}-{RANDOM_STRING}`
- Example: `RANK-2025-A1B2C3D4E5F6`
- Rationale: Human-readable, brand-identifiable, low collision risk

#### 2. **UUID for IDs**
- All primary keys use UUID v4
- Rationale: Distributed systems, privacy, unguessable

#### 3. **API Key Management**
- Separate keys for Brand APIs vs Product APIs
- Rationale: Least privilege principle, different permissions
- Product APIs are read-heavy, can be rate-limited differently

#### 4. **Soft Deletes**
- Uses `deleted_at` timestamp
- Rationale: Auditability, compliance, data recovery

#### 5. **License Status Transitions**
- Not all transitions are allowed
- Business rules enforced in service layer
- Rationale: Data consistency, prevent invalid states

### Error Handling

#### HTTP Status Codes
- **200**: Success
- **201**: Created
- **400**: Bad request (validation error)
- **401**: Unauthorized (invalid API key)
- **403**: Forbidden (no access to resource)
- **404**: Not found
- **409**: Conflict (duplicate, invalid state transition)
- **500**: Server error

#### Error Response Format
```json
{
  "error": {
    "code": "INVALID_LICENSE_KEY",
    "message": "The provided license key is not valid",
    "details": {}
  }
}
```

### Logging & Observability

#### Key Events to Log
1. License provisioning
2. License activation
3. License validation
4. Status changes
5. API authentication failures
6. Data access anomalies

#### Metrics to Track
- License provisioning rate (per brand)
- License validation latency
- License expiration rate
- API error rates
- Database query performance

---

## Future Extensibility

### Planned Features (Designed but not implemented)

#### 1. **Seat Management**
```
Seat (future)
├── id: UUID
├── license_id: UUID
├── assigned_to: string (email)
├── status: enum (active, inactive)
├── assigned_at: datetime
└── activated_at: datetime
```

**API**:
```
POST /api/v1/brands/{brand_id}/licenses/{license_id}/seats
- Request: { "email": "user@example.com" }
- Response: Seat object

GET /api/v1/brands/{brand_id}/licenses/{license_id}/seats
- List all seats for a license

PATCH /api/v1/brands/{brand_id}/seats/{seat_id}
- Reassign or deactivate seat
```

#### 2. **Feature Flags**
Link features to licenses, allowing fine-grained entitlements.

```
LicenseFeature (future)
├── id: UUID
├── license_id: UUID
├── feature_id: UUID
├── enabled: boolean
├── metadata: JSON (feature-specific config)
```

#### 3. **Usage Tracking**
Track API calls, seat usage, feature usage per license.

```
UsageEvent (future)
├── id: UUID
├── license_id: UUID
├── event_type: string
├── metadata: JSON
├── recorded_at: datetime
```

#### 4. **Webhooks & Events**
Notify brands of license events.

```
WebhookSubscription (future)
├── id: UUID
├── brand_id: UUID
├── event_type: string
├── url: string
├── is_active: boolean

Events: license.created, license.activated, license.suspended, etc.
```

#### 5. **License Transfer/Sharing**
Allow customers to transfer licenses or share seats.

#### 6. **Renewal & Upgrade Flows**
Handle license renewals, upgrades, downgrades.

---

## Summary

This design provides:
- **Clear multi-tenancy**: Isolated brands with shared infrastructure
- **Scalable architecture**: Horizontal scaling possible at all layers
- **Auditability**: Full tracking of all operations
- **Extensibility**: Foundation for seats, features, usage tracking
- **Operational clarity**: Well-defined APIs, error handling, logging

**Trade-offs made:**
- Separate API keys for brands/products (simpler than role-based access)
- No built-in seat management (designed, not implemented - can add later)
- Synchronous APIs (good for licensing, easier to implement; async webhooks future)
- Simple key generation (not optimized for compliance with specific enterprise requirements)
