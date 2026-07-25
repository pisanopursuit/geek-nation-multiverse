CREATE TABLE IF NOT EXISTS academy_courses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_user_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  subtitle VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  category VARCHAR(100) NOT NULL DEFAULT 'general',
  level ENUM('beginner','intermediate','advanced','all_levels') NOT NULL DEFAULT 'all_levels',
  format ENUM('self_paced','live','hybrid') NOT NULL DEFAULT 'self_paced',
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  capacity INT DEFAULT NULL,
  status ENUM('draft','pending','approved','rejected','archived') NOT NULL DEFAULT 'pending',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  thumbnail_path VARCHAR(255) DEFAULT NULL,
  banner_path VARCHAR(255) DEFAULT NULL,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  timezone VARCHAR(80) NOT NULL DEFAULT 'America/New_York',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_academy_course_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_academy_course_status (status,is_featured,title),
  INDEX idx_academy_course_owner (owner_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academy_lessons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  lesson_type ENUM('video','article','download','quiz','live_session','assignment') NOT NULL DEFAULT 'article',
  summary TEXT DEFAULT NULL,
  content LONGTEXT DEFAULT NULL,
  media_url VARCHAR(500) DEFAULT NULL,
  attachment_path VARCHAR(255) DEFAULT NULL,
  duration_minutes INT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_preview TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_academy_lesson_course FOREIGN KEY (course_id) REFERENCES academy_courses(id) ON DELETE CASCADE,
  INDEX idx_academy_lesson_course (course_id,sort_order,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academy_enrollments (
  course_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('enrolled','waitlisted','in_progress','completed','cancelled') NOT NULL DEFAULT 'enrolled',
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (course_id,user_id),
  CONSTRAINT fk_academy_enrollment_course FOREIGN KEY (course_id) REFERENCES academy_courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_academy_enrollment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_academy_enrollment_user (user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academy_instructors (
  course_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('lead','instructor','assistant','guest') NOT NULL DEFAULT 'instructor',
  bio_override TEXT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (course_id,user_id),
  CONSTRAINT fk_academy_instructor_course FOREIGN KEY (course_id) REFERENCES academy_courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_academy_instructor_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academy_lesson_progress (
  lesson_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (lesson_id,user_id),
  CONSTRAINT fk_academy_progress_lesson FOREIGN KEY (lesson_id) REFERENCES academy_lessons(id) ON DELETE CASCADE,
  CONSTRAINT fk_academy_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
