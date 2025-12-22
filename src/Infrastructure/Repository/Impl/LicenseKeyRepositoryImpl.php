<?php

namespace App\Infrastructure\Repository\Impl;

use App\Domain\LicenseKey;
use App\Infrastructure\Database;
use App\Infrastructure\Repository\LicenseKeyRepository as LicenseKeyRepositoryInterface;

/**
 * MySQL implementation of LicenseKeyRepository
 */
class LicenseKeyRepositoryImpl implements LicenseKeyRepositoryInterface
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function save(LicenseKey $licenseKey): void
    {
        $this->db->query(
            "INSERT INTO license_keys (id, brand_id, customer_email, `key`, status, created_by_brand_id, created_at, updated_at) 
             VALUES (:id, :brand_id, :customer_email, :key, :status, :created_by_brand_id, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE 
             status = VALUES(status), 
             updated_at = VALUES(updated_at)",
            [
                ':id' => $licenseKey->getId(),
                ':brand_id' => $licenseKey->getBrandId(),
                ':customer_email' => $licenseKey->getCustomerEmail(),
                ':key' => $licenseKey->getKey(),
                ':status' => $licenseKey->getStatus(),
                ':created_by_brand_id' => $licenseKey->getCreatedByBrandId(),
                ':created_at' => $licenseKey->getCreatedAt()->format('Y-m-d H:i:s'),
                ':updated_at' => $licenseKey->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findById(string $id): ?LicenseKey
    {
        $result = $this->db->query(
            "SELECT * FROM license_keys WHERE id = :id",
            [':id' => $id]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByKey(string $key): ?LicenseKey
    {
        $result = $this->db->query(
            "SELECT * FROM license_keys WHERE `key` = :key",
            [':key' => $key]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByBrandAndCustomerEmail(string $brandId, string $customerEmail): array
    {
        $results = $this->db->query(
            "SELECT * FROM license_keys WHERE brand_id = :brand_id AND customer_email = :customer_email ORDER BY created_at DESC",
            [':brand_id' => $brandId, ':customer_email' => $customerEmail]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    public function findByBrandId(string $brandId): array
    {
        $results = $this->db->query(
            "SELECT * FROM license_keys WHERE brand_id = :brand_id ORDER BY created_at DESC",
            [':brand_id' => $brandId]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    public function exists(string $id): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM license_keys WHERE id = :id",
            [':id' => $id]
        )->fetch();

        return $result['count'] > 0;
    }

    private function hydrate(array $row): LicenseKey
    {
        return new LicenseKey(
            $row['id'],
            $row['brand_id'],
            $row['customer_email'],
            $row['key'],
            $row['created_by_brand_id'],
            $row['status'],
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at'])
        );
    }
}
