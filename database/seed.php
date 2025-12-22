<?php

/**
 * Database Seeder
 *
 * Seeds the database with sample brands and products for testing.
 */

require_once __DIR__ . '/../html/autoload.php';
require_once __DIR__ . '/../html/App.php';

$app = new App();

$brandService = $app->getBrandService();
$licenseKeyService = $app->getLicenseKeyService();
$licenseService = $app->getLicenseService();

echo "=== Starting Database Seeder ===\n\n";

try {
    // Create RankMath brand
    echo "Creating RankMath brand...\n";
    try {
        $rankMath = $brandService->registerBrand('RankMath', 'rankmath');
    } catch (App\Domain\InvalidBrandException $e) {
        // If already exists, fetch it so seeding is idempotent
        $rankMath = $brandService->getBrandBySlug('rankmath');
    }
    echo "  - Brand ID: {$rankMath->getId()}\n";
    echo "  - Brand API Key: {$rankMath->getApiKeyBrand()}\n";
    echo "  - Product API Key: {$rankMath->getApiKeyProduct()}\n\n";

    // Create RankMath products
    echo "Creating RankMath products...\n";
    try {
        $rankMathPro = $brandService->createProduct(
            $rankMath->getId(),
            'RankMath Pro',
            'rankmath-pro',
            'SEO plugin for WordPress'
        );
    } catch (App\Domain\InvalidBrandException $e) {
        $rankMathPro = $brandService->getProductByBrandAndSlug($rankMath->getId(), 'rankmath-pro');
    }
    echo "  - Product: RankMath Pro (ID: {$rankMathPro->getId()})\n";

    try {
        $contentAi = $brandService->createProduct(
            $rankMath->getId(),
            'Content AI',
            'content-ai',
            'AI-powered content generation addon for RankMath'
        );
    } catch (App\Domain\InvalidBrandException $e) {
        $contentAi = $brandService->getProductByBrandAndSlug($rankMath->getId(), 'content-ai');
    }
    echo "  - Product: Content AI (ID: {$contentAi->getId()})\n\n";

    // Create WP Rocket brand
    echo "Creating WP Rocket brand...\n";
    try {
        $wpRocket = $brandService->registerBrand('WP Rocket', 'wp-rocket');
    } catch (App\Domain\InvalidBrandException $e) {
        $wpRocket = $brandService->getBrandBySlug('wp-rocket');
    }
    echo "  - Brand ID: {$wpRocket->getId()}\n";
    echo "  - Brand API Key: {$wpRocket->getApiKeyBrand()}\n";
    echo "  - Product API Key: {$wpRocket->getApiKeyProduct()}\n\n";

    // Create WP Rocket product
    echo "Creating WP Rocket product...\n";
    try {
        $wpRocketPlugin = $brandService->createProduct(
            $wpRocket->getId(),
            'WP Rocket',
            'wp-rocket',
            'WordPress caching plugin'
        );
    } catch (App\Domain\InvalidBrandException $e) {
        $wpRocketPlugin = $brandService->getProductByBrandAndSlug($wpRocket->getId(), 'wp-rocket');
    }
    echo "  - Product: WP Rocket (ID: {$wpRocketPlugin->getId()})\n\n";

    // Create sample license for RankMath
    echo "=== Creating Sample License (RankMath Scenario) ===\n\n";

    echo "Creating license key for customer john@example.com...\n";
    $existingKeysRM = $licenseKeyService->getLicenseKeysByCustomer($rankMath->getId(), 'john@example.com');
    if (!empty($existingKeysRM)) {
        $licenseKey1 = $existingKeysRM[0];
    } else {
        $licenseKey1 = $licenseKeyService->createLicenseKey(
            $rankMath->getId(),
            'john@example.com'
        );
    }
    echo "  - License Key: {$licenseKey1->getKey()}\n\n";

    echo "Creating license for RankMath Pro...\n";
    try {
        $license1 = $licenseService->createLicense(
            $licenseKey1->getId(),
            $rankMathPro->getId(),
            new DateTime('now'),
            new DateTime('+1 year')
        );
    } catch (App\Domain\DuplicateLicenseException $e) {
        $license1 = $licenseService->getLicenseByKeyAndProduct($licenseKey1->getId(), $rankMathPro->getId());
        echo "  - License already exists, reusing.\n";
    }
    if ($license1) {
        echo "  - License ID: {$license1->getId()}\n";
        echo "  - Expires: {$license1->getExpiresAt()->format('Y-m-d')}\n\n";
    }

    echo "Adding Content AI addon to same license key...\n";
    try {
        $license2 = $licenseService->createLicense(
            $licenseKey1->getId(),
            $contentAi->getId(),
            new DateTime('now'),
            new DateTime('+1 year')
        );
    } catch (App\Domain\DuplicateLicenseException $e) {
        $license2 = $licenseService->getLicenseByKeyAndProduct($licenseKey1->getId(), $contentAi->getId());
        echo "  - License already exists, reusing.\n";
    }
    if ($license2) {
        echo "  - License ID: {$license2->getId()}\n";
        echo "  - Expires: {$license2->getExpiresAt()->format('Y-m-d')}\n\n";
    }

    // Create sample license for WP Rocket (different brand, different key)
    echo "Creating license for WP Rocket (different brand/key)...\n";
    $existingKeysWPR = $licenseKeyService->getLicenseKeysByCustomer($wpRocket->getId(), 'john@example.com');
    if (!empty($existingKeysWPR)) {
        $licenseKey2 = $existingKeysWPR[0];
    } else {
        $licenseKey2 = $licenseKeyService->createLicenseKey(
            $wpRocket->getId(),
            'john@example.com'
        );
    }
    echo "  - License Key: {$licenseKey2->getKey()}\n\n";

    try {
        $license3 = $licenseService->createLicense(
            $licenseKey2->getId(),
            $wpRocketPlugin->getId(),
            new DateTime('now'),
            new DateTime('+1 year')
        );
    } catch (App\Domain\DuplicateLicenseException $e) {
        $license3 = $licenseService->getLicenseByKeyAndProduct($licenseKey2->getId(), $wpRocketPlugin->getId());
        echo "  - License already exists, reusing.\n";
    }
    if ($license3) {
        echo "  - License ID: {$license3->getId()}\n";
        echo "  - Expires: {$license3->getExpiresAt()->format('Y-m-d')}\n\n";
    }

    // Persist seed output for test script consumption
    $seedOutput = [
        'rankmath' => [
            'brand_id' => $rankMath->getId(),
            'api_key_brand' => $rankMath->getApiKeyBrand(),
            'api_key_product' => $rankMath->getApiKeyProduct(),
            'products' => [
                'rankmath-pro' => $rankMathPro->getId(),
                'content-ai' => $contentAi->getId(),
            ],
            'license_key' => $licenseKey1->getKey(),
            'license_key_id' => $licenseKey1->getId(),
        ],
        'wp-rocket' => [
            'brand_id' => $wpRocket->getId(),
            'api_key_brand' => $wpRocket->getApiKeyBrand(),
            'api_key_product' => $wpRocket->getApiKeyProduct(),
            'products' => [
                'wp-rocket' => $wpRocketPlugin->getId(),
            ],
            'license_key' => $licenseKey2->getKey(),
            'license_key_id' => $licenseKey2->getId(),
        ],
    ];

    file_put_contents(__DIR__ . '/seed_output.json', json_encode($seedOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "=== Seeding Complete! ===\n\n";

    echo "API Testing Info:\n";
    echo "-------------------\n\n";

    echo "RankMath Brand:\n";
    echo "  Brand ID: {$rankMath->getId()}\n";
    echo "  Brand API Key (for provisioning): {$rankMath->getApiKeyBrand()}\n";
    echo "  Product API Key (for validation): {$rankMath->getApiKeyProduct()}\n";
    echo "  License Key: {$licenseKey1->getKey()}\n";
    echo "  Products:\n";
    echo "    - RankMath Pro: {$rankMathPro->getId()}\n";
    echo "    - Content AI: {$contentAi->getId()}\n\n";

    echo "WP Rocket Brand:\n";
    echo "  Brand ID: {$wpRocket->getId()}\n";
    echo "  Brand API Key (for provisioning): {$wpRocket->getApiKeyBrand()}\n";
    echo "  Product API Key (for validation): {$wpRocket->getApiKeyProduct()}\n";
    echo "  License Key: {$licenseKey2->getKey()}\n";
    echo "  Products:\n";
    echo "    - WP Rocket: {$wpRocketPlugin->getId()}\n\n";

    echo "Example API Calls:\n";
    echo "-------------------\n\n";

    echo "1. Validate RankMath Pro License:\n";
    echo "   POST http://localhost:8080/api/v1/products/validate\n";
    echo "   Authorization: Bearer {$rankMath->getApiKeyProduct()}\n";
    echo "   Body: {\"license_key\": \"{$licenseKey1->getKey()}\", \"product_id\": \"{$rankMathPro->getId()}\"}\n\n";

    echo "2. Get License Key Details:\n";
    echo "   GET http://localhost:8080/api/v1/brands/{$rankMath->getId()}/license-keys/{$licenseKey1->getId()}\n";
    echo "   Authorization: Bearer {$rankMath->getApiKeyBrand()}\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
