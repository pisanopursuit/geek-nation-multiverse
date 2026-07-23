CREATE TABLE IF NOT EXISTS universe_chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  universe_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  message VARCHAR(1000) NOT NULL,
  status ENUM('visible','deleted') NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ucm_universe (universe_id,status,id),
  INDEX idx_ucm_user (user_id,created_at),
  CONSTRAINT fk_ucm_universe FOREIGN KEY (universe_id) REFERENCES universes(id) ON DELETE CASCADE,
  CONSTRAINT fk_ucm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
