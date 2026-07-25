CREATE TABLE IF NOT EXISTS artist_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  slug VARCHAR(180) NOT NULL UNIQUE,
  artist_name VARCHAR(180) NOT NULL,
  headline VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  specialties VARCHAR(500) DEFAULT NULL,
  website VARCHAR(255) DEFAULT NULL,
  commission_status ENUM('open','closed','waitlist') NOT NULL DEFAULT 'closed',
  status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  avatar_path VARCHAR(255) DEFAULT NULL,
  banner_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_artist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_artist_status (status,is_featured,artist_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artist_portfolio_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  item_type ENUM('artwork','comic','photography','video','music','writing','model','cosplay','other') NOT NULL DEFAULT 'artwork',
  description TEXT DEFAULT NULL,
  media_path VARCHAR(255) DEFAULT NULL,
  external_url VARCHAR(255) DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_portfolio_artist FOREIGN KEY (artist_id) REFERENCES artist_profiles(id) ON DELETE CASCADE,
  INDEX idx_portfolio_artist (artist_id,sort_order,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artist_commission_services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT DEFAULT NULL,
  price_from DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  turnaround_days INT DEFAULT NULL,
  slots_available INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_service_artist FOREIGN KEY (artist_id) REFERENCES artist_profiles(id) ON DELETE CASCADE,
  INDEX idx_service_artist (artist_id,is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artist_commission_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED DEFAULT NULL,
  customer_user_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL,
  brief TEXT NOT NULL,
  budget DECIMAL(10,2) DEFAULT NULL,
  status ENUM('submitted','reviewing','accepted','in_progress','proof','completed','declined','cancelled') NOT NULL DEFAULT 'submitted',
  artist_notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_request_artist FOREIGN KEY (artist_id) REFERENCES artist_profiles(id) ON DELETE CASCADE,
  CONSTRAINT fk_request_service FOREIGN KEY (service_id) REFERENCES artist_commission_services(id) ON DELETE SET NULL,
  CONSTRAINT fk_request_customer FOREIGN KEY (customer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_request_artist (artist_id,status),
  INDEX idx_request_customer (customer_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artist_follows (
  artist_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (artist_id,user_id),
  CONSTRAINT fk_follow_artist FOREIGN KEY (artist_id) REFERENCES artist_profiles(id) ON DELETE CASCADE,
  CONSTRAINT fk_follow_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
