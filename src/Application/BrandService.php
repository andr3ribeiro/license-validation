<?php

namespace App\Application;

use App\Domain\{
    Brand,
    BrandNotFoundException,
    InvalidBrandException,
    Product,
    ProductNotFoundException
};
use App\Infrastructure\IdGenerator;
use App\Infrastructure\Repository\{
    BrandRepository,
    ProductRepository
};

/**
 * Service for managing brands
 * 
 * Handles brand registration, activation, and product management.
 */
class BrandService
{
    private BrandRepository $brandRepo;
    private ProductRepository $productRepo;

    public function __construct(
        BrandRepository $brandRepo,
        ProductRepository $productRepo
    ) {
        $this->brandRepo = $brandRepo;
        $this->productRepo = $productRepo;
    }

    /**
     * Register a new brand
     * 
     * @throws InvalidBrandException
     */
    public function registerBrand(string $name, string $slug): Brand
    {
        // Check if slug already exists
        $existing = $this->brandRepo->findBySlug($slug);
        if ($existing !== null) {
            throw new InvalidBrandException("Brand slug already exists: $slug");
        }

        $brand = new Brand(
            IdGenerator::generateUuid(),
            $name,
            $slug,
            IdGenerator::generateApiKey(),
            IdGenerator::generateApiKey()
        );

        $this->brandRepo->save($brand);

        return $brand;
    }

    /**
     * Get brand by ID
     * 
     * @throws BrandNotFoundException
     */
    public function getBrand(string $brandId): Brand
    {
        $brand = $this->brandRepo->findById($brandId);
        if ($brand === null) {
            throw new BrandNotFoundException("Brand not found: $brandId");
        }

        return $brand;
    }

    /**
     * Get brand by slug
     * 
     * @throws BrandNotFoundException
     */
    public function getBrandBySlug(string $slug): Brand
    {
        $brand = $this->brandRepo->findBySlug($slug);
        if ($brand === null) {
            throw new BrandNotFoundException("Brand not found: $slug");
        }

        return $brand;
    }

    /**
     * Get product by brand and slug
     * 
     * @throws ProductNotFoundException
     */
    public function getProductByBrandAndSlug(string $brandId, string $slug): Product
    {
        $product = $this->productRepo->findByBrandAndSlug($brandId, $slug);
        if ($product === null) {
            throw new ProductNotFoundException("Product not found: $slug");
        }

        return $product;
    }

    /**
     * Authenticate brand by provisioning API key
     * 
     * @throws UnauthorizedException
     */
    public function authenticateBrandByProvisioningKey(string $apiKey): Brand
    {
        $brand = $this->brandRepo->findByApiKeyBrand($apiKey);
        if ($brand === null || !$brand->isActive()) {
            throw new \App\Domain\UnauthorizedException("Invalid or inactive brand API key");
        }

        return $brand;
    }

    /**
     * Authenticate brand by validation API key
     * 
     * @throws UnauthorizedException
     */
    public function authenticateBrandByValidationKey(string $apiKey): Brand
    {
        $brand = $this->brandRepo->findByApiKeyProduct($apiKey);
        if ($brand === null || !$brand->isActive()) {
            throw new \App\Domain\UnauthorizedException("Invalid or inactive brand API key");
        }

        return $brand;
    }

    /**
     * Create a product for a brand
     * 
     * @throws BrandNotFoundException
     * @throws InvalidBrandException
     */
    public function createProduct(
        string $brandId,
        string $name,
        string $slug,
        string $description = ''
    ): Product {
        // Validate brand exists
        $brand = $this->brandRepo->findById($brandId);
        if ($brand === null) {
            throw new BrandNotFoundException("Brand not found: $brandId");
        }

        if (!$brand->isActive()) {
            throw new InvalidBrandException("Brand is not active");
        }

        // Check if product slug already exists for this brand
        $existing = $this->productRepo->findByBrandAndSlug($brandId, $slug);
        if ($existing !== null) {
            throw new InvalidBrandException("Product slug already exists for this brand");
        }

        $product = new Product(
            IdGenerator::generateUuid(),
            $brandId,
            $name,
            $slug,
            $description
        );

        $this->productRepo->save($product);

        return $product;
    }

    /**
     * Get product
     * 
     * @throws ProductNotFoundException
     */
    public function getProduct(string $productId): Product
    {
        $product = $this->productRepo->findById($productId);
        if ($product === null) {
            throw new ProductNotFoundException("Product not found: $productId");
        }

        return $product;
    }

    /**
     * Get all products for a brand
     */
    public function getBrandProducts(string $brandId): array
    {
        return $this->productRepo->findByBrandId($brandId);
    }

    /**
     * Get active products for a brand
     */
    public function getActiveBrandProducts(string $brandId): array
    {
        return $this->productRepo->findActiveByBrandId($brandId);
    }
}
