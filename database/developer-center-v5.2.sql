CREATE TABLE IF NOT EXISTS developer_demo_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 batch_key VARCHAR(80) NOT NULL UNIQUE,
 label VARCHAR(190) NOT NULL,
 scenario ENUM('complete','users','organizations','universes','community','booths','marketplace') NOT NULL DEFAULT 'complete',
 status ENUM('building','ready','partial','cleaned','failed') NOT NULL DEFAULT 'building',
 created_by BIGINT UNSIGNED NOT NULL,
 summary_json JSON NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 completed_at DATETIME NULL,
 cleaned_at DATETIME NULL,
 INDEX idx_dev_batches_status(status,created_at),
 CONSTRAINT fk_dev_batch_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS developer_demo_records (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 batch_id BIGINT UNSIGNED NOT NULL,
 module_name VARCHAR(60) NOT NULL,
 table_name VARCHAR(100) NOT NULL,
 record_key VARCHAR(190) NOT NULL,
 cleanup_order INT NOT NULL DEFAULT 100,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_dev_records_batch(batch_id,cleanup_order,id),
 CONSTRAINT fk_dev_record_batch FOREIGN KEY(batch_id) REFERENCES developer_demo_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
