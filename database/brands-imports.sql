CREATE TABLE IF NOT EXISTS brands (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(180) NOT NULL UNIQUE,
 short_description VARCHAR(280) NULL,
 description TEXT NULL,
 logo_path VARCHAR(255) NULL,
 banner_path VARCHAR(255) NULL,
 website VARCHAR(255) NULL,
 public_email VARCHAR(190) NULL,
 category VARCHAR(120) NULL,
 founded_year SMALLINT UNSIGNED NULL,
 status ENUM('draft','pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
 submitted_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NULL,
 review_notes TEXT NULL,
 import_batch_id BIGINT UNSIGNED NULL,
 submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_brand_company FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,
 CONSTRAINT fk_brand_submitter FOREIGN KEY(submitted_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_brand_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_brand_company(company_id), INDEX idx_brand_status(status), INDEX idx_brand_name(name), INDEX idx_brand_batch(import_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brand_members (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 brand_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 position_title VARCHAR(140) NULL,
 brand_role ENUM('pending_manager','manager','member') NOT NULL DEFAULT 'member',
 status ENUM('pending','active','rejected','removed') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_brand_member_brand FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE CASCADE,
 CONSTRAINT fk_brand_member_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 UNIQUE KEY uq_brand_user(brand_id,user_id), INDEX idx_brand_member_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brand_social_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 brand_id BIGINT UNSIGNED NOT NULL,
 platform VARCHAR(50) NOT NULL,
 url VARCHAR(255) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 CONSTRAINT fk_brand_social_brand FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE CASCADE,
 INDEX idx_brand_social(brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brand_approval_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 brand_id BIGINT UNSIGNED NOT NULL,
 action ENUM('submitted','approved','rejected','suspended','restored') NOT NULL,
 notes TEXT NULL,
 acted_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_brand_history_brand FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE CASCADE,
 CONSTRAINT fk_brand_history_user FOREIGN KEY(acted_by) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX idx_brand_history(brand_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 entity_type ENUM('company','brand') NOT NULL,
 original_filename VARCHAR(255) NOT NULL,
 duplicate_action ENUM('skip','update') NOT NULL DEFAULT 'skip',
 status ENUM('processing','completed','completed_with_errors','rolled_back','failed') NOT NULL DEFAULT 'processing',
 total_rows INT UNSIGNED NOT NULL DEFAULT 0,
 imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
 updated_rows INT UNSIGNED NOT NULL DEFAULT 0,
 skipped_rows INT UNSIGNED NOT NULL DEFAULT 0,
 error_rows INT UNSIGNED NOT NULL DEFAULT 0,
 imported_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 completed_at DATETIME NULL,
 CONSTRAINT fk_import_user FOREIGN KEY(imported_by) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX idx_import_created(created_at), INDEX idx_import_type(entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 batch_id BIGINT UNSIGNED NOT NULL,
 import_row INT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 action ENUM('imported','updated','skipped','error') NOT NULL,
 source_key VARCHAR(255) NULL,
 message TEXT NULL,
 row_data JSON NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_import_item_batch FOREIGN KEY(batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
 INDEX idx_import_item_batch(batch_id), INDEX idx_import_item_action(action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
