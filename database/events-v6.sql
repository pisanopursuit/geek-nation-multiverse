CREATE TABLE IF NOT EXISTS events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 slug VARCHAR(200) NOT NULL UNIQUE,
 subtitle VARCHAR(255) NULL,
 description TEXT NULL,
 event_type VARCHAR(80) NOT NULL DEFAULT 'panel',
 status ENUM('draft','pending','approved','rejected','cancelled','completed','archived') NOT NULL DEFAULT 'pending',
 visibility ENUM('public','members','private') NOT NULL DEFAULT 'public',
 format ENUM('virtual','physical','hybrid') NOT NULL DEFAULT 'virtual',
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 timezone VARCHAR(80) NOT NULL DEFAULT 'America/New_York',
 venue_name VARCHAR(190) NULL,
 address1 VARCHAR(255) NULL,
 address2 VARCHAR(255) NULL,
 city VARCHAR(120) NULL,
 state_region VARCHAR(120) NULL,
 postal_code VARCHAR(30) NULL,
 country VARCHAR(120) NULL,
 virtual_url VARCHAR(500) NULL,
 capacity INT UNSIGNED NULL,
 registration_mode ENUM('open','approval','invite','closed') NOT NULL DEFAULT 'open',
 waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1,
 comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
 banner_path VARCHAR(255) NULL,
 thumbnail_path VARCHAR(255) NULL,
 is_featured TINYINT(1) NOT NULL DEFAULT 0,
 admin_notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_events_status_dates(status,starts_at), INDEX idx_events_owner(owner_user_id), INDEX idx_events_type(event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_relationships (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 entity_type ENUM('company','brand','booth','universe') NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_event_relationship(event_id,entity_type,entity_id),
 INDEX idx_event_relationship_entity(entity_type,entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_speakers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 name VARCHAR(190) NOT NULL,
 role VARCHAR(80) NOT NULL DEFAULT 'Speaker',
 bio TEXT NULL,
 image_path VARCHAR(255) NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_event_speakers(event_id,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_attendees (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 attendee_status ENUM('registered','waitlisted','approved','checked_in','cancelled') NOT NULL DEFAULT 'registered',
 guest_count INT UNSIGNED NOT NULL DEFAULT 1,
 note TEXT NULL,
 registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 checked_in_at DATETIME NULL,
 UNIQUE KEY uq_event_attendee(event_id,user_id),
 INDEX idx_event_attendee_status(event_id,attendee_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_media (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 media_type ENUM('image','attachment','livestream','recording') NOT NULL DEFAULT 'image',
 title VARCHAR(190) NULL,
 file_path VARCHAR(255) NULL,
 external_url VARCHAR(500) NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_event_media(event_id,media_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_views (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 viewer_user_id BIGINT UNSIGNED NULL,
 session_key CHAR(64) NOT NULL,
 viewed_on DATE NOT NULL,
 UNIQUE KEY uq_event_view(event_id,session_key,viewed_on),
 INDEX idx_event_views(event_id,viewed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
