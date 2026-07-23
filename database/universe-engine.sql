-- Geek Nation Multiverse Version 4: Universe Engine reference schema.
-- Use upgrade-universes.php on an existing installation.

CREATE TABLE IF NOT EXISTS universe_moderators (
  universe_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('owner','moderator') NOT NULL DEFAULT 'moderator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (universe_id,user_id),
  CONSTRAINT fk_um_universe FOREIGN KEY (universe_id) REFERENCES universes(id) ON DELETE CASCADE,
  CONSTRAINT fk_um_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS universe_activity (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  universe_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  details TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ua_universe_created (universe_id,created_at),
  CONSTRAINT fk_ua_universe FOREIGN KEY (universe_id) REFERENCES universes(id) ON DELETE CASCADE,
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
