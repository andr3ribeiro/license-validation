-- Multi-Tenant License Service Database Schema
-- Version: 1.0
DROP DATABASE IF EXISTS license_service;
CREATE DATABASE IF NOT EXISTS license_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE license_service;

-- Brands (Tenants)
CREATE TABLE IF NOT EXISTS brands (
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
    INDEX idx_api_key_brand (api_key_brand),
    INDEX idx_api_key_product (api_key_product)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(36) PRIMARY KEY,
    brand_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    UNIQUE KEY unique_brand_product_slug (brand_id, slug),
    INDEX idx_brand_id (brand_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- License Keys
CREATE TABLE IF NOT EXISTS license_keys (
    id VARCHAR(36) PRIMARY KEY,
    brand_id VARCHAR(36) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
    created_by_brand_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    INDEX idx_brand_customer (brand_id, customer_email),
    INDEX idx_key (`key`),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Licenses
CREATE TABLE IF NOT EXISTS licenses (
    id VARCHAR(36) PRIMARY KEY,
    license_key_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    seat_limit INT NULL,
    status ENUM('valid', 'suspended', 'cancelled', 'expired') DEFAULT 'valid',
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    activated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_key_id) REFERENCES license_keys(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_key_product (license_key_id, product_id),
    INDEX idx_license_key_id (license_key_id),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- License Activations (Optional - for tracking activation events)
CREATE TABLE IF NOT EXISTS license_activations (
    id VARCHAR(36) PRIMARY KEY,
    license_id VARCHAR(36) NOT NULL,
    instance_id VARCHAR(255) NOT NULL,
    activated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_agent VARCHAR(500),
    ip_address VARCHAR(45),
    activation_source VARCHAR(100),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_license_instance (license_id, instance_id),
    INDEX idx_license_id (license_id),
    INDEX idx_activated_at (activated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log
CREATE TABLE IF NOT EXISTS audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(36) NOT NULL,
    action VARCHAR(50) NOT NULL,
    brand_id VARCHAR(36),
    actor_type VARCHAR(50),
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_brand_created (brand_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
