
CREATE TABLE IF NOT EXISTS booth_team_members (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booth_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 role ENUM('manager','staff','artist','moderator') NOT NULL DEFAULT 'staff',
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_booth_team_user(booth_id,user_id),
 INDEX idx_booth_team(booth_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_gallery (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booth_id BIGINT UNSIGNED NOT NULL,
 image_path VARCHAR(255) NOT NULL,
 caption VARCHAR(255) NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_booth_gallery(booth_id,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_downloads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booth_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 description TEXT NULL,
 file_path VARCHAR(255) NULL,
 external_url VARCHAR(500) NULL,
 is_public TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_booth_downloads(booth_id,is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booth_views (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booth_id BIGINT UNSIGNED NOT NULL,
 viewer_user_id BIGINT UNSIGNED NULL,
 session_key VARCHAR(128) NULL,
 viewed_on DATE NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_booth_view_day(booth_id,session_key,viewed_on),
 INDEX idx_booth_views(booth_id,viewed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
