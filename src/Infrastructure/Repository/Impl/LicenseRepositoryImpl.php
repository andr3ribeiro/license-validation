<?php

namespace App\Infrastructure\Repository\Impl;

use App\Domain\License;
use App\Infrastructure\Database;
use App\Infrastructure\Repository\LicenseRepository as LicenseRepositoryInterface;

/**
 * MySQL implementation of LicenseRepository
 */
class LicenseRepositoryImpl implements LicenseRepositoryInterface
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function save(License $license): void
    {
        $this->db->query(
            "INSERT INTO licenses (id, license_key_id, product_id, status, starts_at, expires_at, activated_at, created_at, updated_at) 
             VALUES (:id, :license_key_id, :product_id, :status, :starts_at, :expires_at, :activated_at, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE 
             status = VALUES(status), 
             activated_at = VALUES(activated_at), 
             updated_at = VALUES(updated_at)",
            [
                ':id' => $license->getId(),
                ':license_key_id' => $license->getLicenseKeyId(),
                ':product_id' => $license->getProductId(),
                ':status' => $license->getStatus(),
                ':starts_at' => $license->getStartsAt()->format('Y-m-d H:i:s'),
                ':expires_at' => $license->getExpiresAt()->format('Y-m-d H:i:s'),
                ':activated_at' => $license->getActivatedAt()?->format('Y-m-d H:i:s'),
                ':created_at' => $license->getCreatedAt()->format('Y-m-d H:i:s'),
                ':updated_at' => $license->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findById(string $id): ?License
    {
        $result = $this->db->query(
            "SELECT * FROM licenses WHERE id = :id",
            [':id' => $id]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByLicenseKeyId(string $licenseKeyId): array
    {
        $results = $this->db->query(
            "SELECT * FROM licenses WHERE license_key_id = :license_key_id ORDER BY created_at DESC",
            [':license_key_id' => $licenseKeyId]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    public function findByKeyAndProduct(string $licenseKeyId, string $productId): ?License
    {
        $result = $this->db->query(
            "SELECT * FROM licenses WHERE license_key_id = :license_key_id AND product_id = :product_id",
            [':license_key_id' => $licenseKeyId, ':product_id' => $productId]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByProductId(string $productId): array
    {
        $results = $this->db->query(
            "SELECT * FROM licenses WHERE product_id = :product_id ORDER BY created_at DESC",
            [':product_id' => $productId]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    public function findValidByBrandId(string $brandId): array
    {
        $results = $this->db->query(
            "SELECT l.* FROM licenses l 
             INNER JOIN license_keys lk ON l.license_key_id = lk.id 
             WHERE lk.brand_id = :brand_id AND l.status IN ('valid', 'suspended')
             ORDER BY l.created_at DESC",
            [':brand_id' => $brandId]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    public function findExpired(): array
    {
        $results = $this->db->query(
            "SELECT * FROM licenses WHERE expires_at < NOW() AND status != 'expired'"
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    private function hydrate(array $row): License
    {
        return new License(
            $row['id'],
            $row['license_key_id'],
            $row['product_id'],
            new \DateTime($row['starts_at']),
            new \DateTime($row['expires_at']),
            $row['status'],
            $row['activated_at'] ? new \DateTime($row['activated_at']) : null,
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at'])
        );
    }
}
