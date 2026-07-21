CREATE TABLE IF NOT EXISTS companies (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(180) NOT NULL UNIQUE,
 short_description VARCHAR(280) NULL,
 description TEXT NULL,
 logo_path VARCHAR(255) NULL,
 banner_path VARCHAR(255) NULL,
 website VARCHAR(255) NULL,
 public_email VARCHAR(190) NULL,
 phone VARCHAR(60) NULL,
 location VARCHAR(180) NULL,
 category VARCHAR(120) NULL,
 founded_year SMALLINT UNSIGNED NULL,
 status ENUM('draft','pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
 submitted_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NULL,
 review_notes TEXT NULL,
 submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_company_submitter FOREIGN KEY(submitted_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_company_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_company_status(status), INDEX idx_company_name(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_members (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 relationship_type ENUM('employee','founder','owner','executive','contractor','partner','advisor','fan','other') NOT NULL,
 position_title VARCHAR(140) NULL,
 company_role ENUM('pending_owner','owner','company_admin','member','fan') NOT NULL DEFAULT 'member',
 status ENUM('pending','active','rejected','removed') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_company_member_company FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,
 CONSTRAINT fk_company_member_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 UNIQUE KEY uq_company_user(company_id,user_id),
 INDEX idx_company_member_user(user_id), INDEX idx_company_member_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_social_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_id BIGINT UNSIGNED NOT NULL,
 platform VARCHAR(50) NOT NULL,
 url VARCHAR(255) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 CONSTRAINT fk_company_social_company FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,
 INDEX idx_company_social(company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_approval_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_id BIGINT UNSIGNED NOT NULL,
 action ENUM('submitted','approved','rejected','suspended','restored') NOT NULL,
 notes TEXT NULL,
 acted_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_company_history_company FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,
 CONSTRAINT fk_company_history_user FOREIGN KEY(acted_by) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX idx_company_history(company_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
