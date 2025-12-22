<?php

namespace App\Http;

use App\Application\{BrandService, LicenseKeyService, LicenseService};
use App\Domain\{
    BrandNotFoundException,
    DuplicateLicenseException,
    InvalidBrandException,
    LicenseKeyNotFoundException,
    LicenseNotFoundException,
    ProductNotFoundException,
    UnauthorizedException
};

/**
 * Base HTTP Controller with common response formatting
 */
abstract class Controller
{
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    protected function errorResponse(string $code, string $message, int $statusCode = 400): void
    {
        $this->jsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ], $statusCode);
    }

    protected function createdResponse(array $data): void
    {
        $this->jsonResponse($data, 201);
    }

    /**
     * Get JSON request body
     */
    protected function getJsonBody(): array
    {
        $content = file_get_contents('php://input');
        return json_decode($content, true) ?? [];
    }

    /**
     * Get query parameter
     */
    protected function getQueryParam(string $name, ?string $default = null): ?string
    {
        return $_GET[$name] ?? $default;
    }

    /**
     * Get route parameter (set by router)
     */
    protected function getRouteParam(string $name): ?string
    {
        return $_GET["_route_param_$name"] ?? null;
    }

    /**
     * Get authorization header
     */
    protected function getAuthorizationHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if ($header && strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }

        return null;
    }
}
