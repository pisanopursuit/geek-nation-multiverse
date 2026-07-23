-- Version 4.3: Universe Billboard conversations
CREATE TABLE IF NOT EXISTS universe_posts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  universe_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  parent_post_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  status ENUM('visible','hidden','deleted') NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_up_universe_thread (universe_id,parent_post_id,status,created_at),
  INDEX idx_up_user (user_id,created_at),
  CONSTRAINT fk_up_universe FOREIGN KEY (universe_id) REFERENCES universes(id) ON DELETE CASCADE,
  CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_up_parent FOREIGN KEY (parent_post_id) REFERENCES universe_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
