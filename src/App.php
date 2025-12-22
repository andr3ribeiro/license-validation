<?php

use App\Infrastructure\Database;
use App\Infrastructure\Repository\Impl\{
    BrandRepositoryImpl,
    LicenseKeyRepositoryImpl,
    LicenseRepositoryImpl,
    ProductRepositoryImpl
};
use App\Application\{
    BrandService,
    LicenseKeyService,
    LicenseService
};
use App\Http\Controllers\{
    BrandController,
    ProductController
};
use App\Http\Router;

/**
 * Bootstrap the application
 * 
 * This sets up:
 * - Database connection
 * - Dependency injection
 * - Routing
 */
class App
{
    private Database $db;
    private Router $router;

    // Services
    private BrandService $brandService;
    private LicenseKeyService $licenseKeyService;
    private LicenseService $licenseService;

    // Controllers
    private BrandController $brandController;
    private ProductController $productController;

    public function __construct()
    {
        $this->setupDatabase();
        $this->setupDependencies();
        $this->setupRoutes();
    }

    /**
     * Setup database connection
     */
    private function setupDatabase(): void
    {
        $this->db = Database::getInstance();
        
        // Get config from environment
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: 3306;
        $dbname = getenv('DB_NAME') ?: 'license_service';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: 'root_password';

        $this->db->connect($host, $dbname, $user, $password, (int)$port);
    }

    /**
     * Setup dependency injection
     */
    private function setupDependencies(): void
    {
        // Repositories
        $brandRepo = new BrandRepositoryImpl();
        $licenseKeyRepo = new LicenseKeyRepositoryImpl();
        $licenseRepo = new LicenseRepositoryImpl();
        $productRepo = new ProductRepositoryImpl();

        // Services
        $this->brandService = new BrandService($brandRepo, $productRepo);
        $this->licenseKeyService = new LicenseKeyService($licenseKeyRepo, $brandRepo);
        $this->licenseService = new LicenseService($licenseRepo, $licenseKeyRepo, $productRepo, $brandRepo);

        // Controllers
        $this->brandController = new BrandController(
            $this->brandService,
            $this->licenseKeyService,
            $this->licenseService
        );
        $this->productController = new ProductController(
            $this->brandService,
            $this->licenseKeyService,
            $this->licenseService
        );
    }

    /**
     * Setup routes
     */
    private function setupRoutes(): void
    {
        $this->router = new Router();

        // Brand Provisioning APIs
        $this->router->post(
            '/api/v1/brands/{brandId}/license-keys',
            [$this->brandController, 'createLicenseKey']
        );
        $this->router->get(
            '/api/v1/brands/{brandId}/license-keys/{licenseKeyId}',
            [$this->brandController, 'getLicenseKey']
        );
        $this->router->post(
            '/api/v1/brands/{brandId}/licenses',
            [$this->brandController, 'createLicense']
        );
        $this->router->patch(
            '/api/v1/brands/{brandId}/licenses/{licenseId}',
            [$this->brandController, 'updateLicense']
        );

        // Product APIs
        $this->router->post(
            '/api/v1/products/validate',
            [$this->productController, 'validateLicense']
        );
        $this->router->post(
            '/api/v1/products/activate',
            [$this->productController, 'activateLicense']
        );
        $this->router->get(
            '/api/v1/products/licenses/{licenseKey}',
            [$this->productController, 'getLicensesByKey']
        );

        // Health check
        $this->router->get('/health', function() {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok']);
        });
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        // Set CORS headers (for development)
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
            exit(0);
        }

        // Get request method and path
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Dispatch
        try {
            $this->router->dispatch($method, $path);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    /**
     * Get services (for CLI tools, seeding, etc.)
     */
    public function getBrandService(): BrandService
    {
        return $this->brandService;
    }

    public function getLicenseKeyService(): LicenseKeyService
    {
        return $this->licenseKeyService;
    }

    public function getLicenseService(): LicenseService
    {
        return $this->licenseService;
    }
}
