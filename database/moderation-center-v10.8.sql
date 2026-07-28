CREATE TABLE IF NOT EXISTS moderation_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  content_type VARCHAR(40) NOT NULL,
  content_id BIGINT UNSIGNED NOT NULL,
  previous_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  notes TEXT NULL,
  acted_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_moderation_content (content_type,content_id),
  KEY idx_moderation_actor (acted_by),
  CONSTRAINT fk_moderation_actor FOREIGN KEY (acted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
