CREATE TABLE IF NOT EXISTS booths (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NULL,
 brand_id BIGINT UNSIGNED NULL,
 name VARCHAR(180) NOT NULL,
 slug VARCHAR(190) NOT NULL UNIQUE,
 tagline VARCHAR(255) NULL,
 description TEXT NULL,
 logo_path VARCHAR(255) NULL,
 banner_path VARCHAR(255) NULL,
 website VARCHAR(255) NULL,
 contact_email VARCHAR(190) NULL,
 commerce_mode ENUM('demo','external','disabled') NOT NULL DEFAULT 'demo',
 external_store_url VARCHAR(500) NULL,
 status ENUM('draft','pending','approved','rejected','suspended','archived') NOT NULL DEFAULT 'pending',
 is_featured TINYINT(1) NOT NULL DEFAULT 0,
 admin_notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_booths_status(status), INDEX idx_booths_owner(owner_user_id), INDEX idx_booths_company(company_id), INDEX idx_booths_brand(brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_universes (
 booth_id BIGINT UNSIGNED NOT NULL,
 universe_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(booth_id,universe_id), INDEX idx_bu_universe(universe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booth_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(190) NOT NULL,
 slug VARCHAR(200) NOT NULL,
 description TEXT NULL,
 image_path VARCHAR(255) NULL,
 product_type ENUM('physical','digital','service') NOT NULL DEFAULT 'physical',
 price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 compare_at_price DECIMAL(10,2) NULL,
 sku VARCHAR(100) NULL,
 inventory_quantity INT NULL,
 shipping_note VARCHAR(255) NULL,
 download_note VARCHAR(255) NULL,
 convention_exclusive TINYINT(1) NOT NULL DEFAULT 0,
 signed_item TINYINT(1) NOT NULL DEFAULT 0,
 preorder TINYINT(1) NOT NULL DEFAULT 0,
 is_featured TINYINT(1) NOT NULL DEFAULT 0,
 status ENUM('draft','active','sold_out','archived') NOT NULL DEFAULT 'draft',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_booth_product_slug(booth_id,slug), INDEX idx_products_booth_status(booth_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_number VARCHAR(40) NOT NULL UNIQUE,
 booth_id BIGINT UNSIGNED NOT NULL,
 customer_user_id BIGINT UNSIGNED NULL,
 customer_name VARCHAR(190) NOT NULL,
 customer_email VARCHAR(190) NOT NULL,
 shipping_address1 VARCHAR(255) NULL,
 shipping_address2 VARCHAR(255) NULL,
 shipping_city VARCHAR(120) NULL,
 shipping_state VARCHAR(120) NULL,
 shipping_postal VARCHAR(30) NULL,
 shipping_country VARCHAR(120) NULL,
 customer_note TEXT NULL,
 subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 currency CHAR(3) NOT NULL DEFAULT 'USD',
 order_status ENUM('pending','confirmed','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
 payment_status ENUM('not_required','pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
 payment_provider ENUM('demo','stripe','paypal','square','external') NOT NULL DEFAULT 'demo',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_orders_booth(booth_id), INDEX idx_orders_customer(customer_user_id), INDEX idx_orders_status(order_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 product_name VARCHAR(190) NOT NULL,
 sku VARCHAR(100) NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 line_total DECIMAL(10,2) NOT NULL,
 INDEX idx_order_items_order(order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_activity (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booth_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(80) NOT NULL,
 details TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_booth_activity(booth_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;