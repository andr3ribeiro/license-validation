# Quick Start Guide

Get the License Service running in under 2 minutes!

## Prerequisites

- Docker & Docker Compose installed
- curl (for testing)

## Step 1: Clone & Navigate

```bash
git clone https://github.com/andr3ribeiro/license-validation.git
cd license-validation

```

## Step 2: Start Services

Use setup.sh to build and start all services

or:

```bash
docker compose up -d --build
```

Wait ~20 seconds for services to start.

## Step 3: Initialize Database

```bash
docker exec -i mariadb mariadb -uroot -proot_password < database/schema.sql
```

## Step 4: Test Health

```bash
curl http://localhost:8080/health
```

Expected: `{"status":"ok"}`

## Step 5: Create Test Data

### Create a Brand and Products (Manual)

Or use the pre-created test data:

**RankMath Brand:**
- Brand ID: `f6b16578-20c9-4b86-b6cc-7afb77f1c81f`
- Brand API Key: `sk_fab6ba8f61b16c87cf0e9b0babfb5bcceed6a15d9accf7304553280d167197d9`
- Product API Key: `sk_5d006a8b46b62823d05298a23bdced8b89e5b9f7ca5bbf3d8e511ebe8bf3da82`

**Products:**
- RankMath Pro: `41da008a-1026-4ca2-aeed-9f0ba22ba2b3`
- Content AI: `62cce695-5b2c-4d91-a728-552ad59785ab`

## Step 6: Test the API

### Create License Key

```bash
curl -X POST http://localhost:8080/api/v1/brands/f6b16578-20c9-4b86-b6cc-7afb77f1c81f/license-keys \
  -H "Authorization: Bearer sk_fab6ba8f61b16c87cf0e9b0babfb5bcceed6a15d9accf7304553280d167197d9" \
  -H "Content-Type: application/json" \
  -d '{"customer_email": "test@example.com"}'
```

Save the returned `key` value!

### Create License

```bash
curl -X POST http://localhost:8080/api/v1/brands/f6b16578-20c9-4b86-b6cc-7afb77f1c81f/licenses \
  -H "Authorization: Bearer sk_fab6ba8f61b16c87cf0e9b0babfb5bcceed6a15d9accf7304553280d167197d9" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key_id": "YOUR_LICENSE_KEY_ID",
    "product_id": "41da008a-1026-4ca2-aeed-9f0ba22ba2b3",
    "starts_at": "2025-12-22T00:00:00Z",
    "expires_at": "2026-12-22T00:00:00Z"
  }'
```

### Validate License

```bash
curl -X POST http://localhost:8080/api/v1/products/validate \
  -H "Authorization: Bearer sk_5d006a8b46b62823d05298a23bdced8b89e5b9f7ca5bbf3d8e511ebe8bf3da82" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "YOUR_LICENSE_KEY",
    "product_id": "41da008a-1026-4ca2-aeed-9f0ba22ba2b3"
  }'
```

## Done!

You now have a fully functional multi-tenant license service!

## Testing User Stories

### User Story 4: Check License Status

**Test US4 - Check license status and entitlements:**

1. **Check license key details (shows all licenses and seat limits):**
```bash
curl http://localhost:8080/api/v1/brands/{brand_id}/license-keys/{license_key_id} \
  -H "Authorization: Bearer {brand_api_key}"
```

2. **Validate a license (check if valid and get entitlements):**
```bash
curl -X POST http://localhost:8080/api/v1/products/validate \
  -H "Authorization: Bearer {product_api_key}" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "YOUR_LICENSE_KEY",
    "product_id": "YOUR_PRODUCT_ID"
  }'
```

3. **Run the complete test suite (includes US3 and US4 tests):**
```bash
./test-api.sh
```

The test script demonstrates:
- Creating licenses with seat limits (US3)
- Checking license status before/after activation (US4)
- Validating licenses via Product API (US4)
- Viewing seat limits and expiration dates (US4)
- Blocking activations that exceed seat limits (US3)

## Next Steps

- Read [README.md](README.md) for complete API documentation
- Read [DESIGN.md](DESIGN.md) for architecture details
- Read [TEST_RESULTS.md](TEST_RESULTS.md) for test evidence
- Check [IMPLEMENTATION.md](IMPLEMENTATION.md) for implementation summary

## Troubleshooting

### Services not starting?
```bash
docker compose down
docker compose up -d --build
```

### Database connection errors?
Check that MariaDB is running:
```bash
docker compose ps
```

### Can't connect to API?
Verify nginx is running:
```bash
curl http://localhost:8080/health
```

## Stopping Services

```bash
docker compose down
```

To remove volumes (delete database):
```bash
docker compose down -v
```

---