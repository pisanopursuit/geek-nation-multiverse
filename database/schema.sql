CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 display_name VARCHAR(120) NOT NULL,
 role ENUM('fan','creator','vendor','admin') NOT NULL DEFAULT 'fan',
 status ENUM('pending_email','active','suspended') NOT NULL DEFAULT 'pending_email',
 company_brand_access ENUM('not_requested','pending','approved','rejected') NOT NULL DEFAULT 'not_requested',
 email_verified_at DATETIME NULL,
 last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_users_status(status), INDEX idx_users_access(company_brand_access)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS email_verifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_ev_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS password_resets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_pr_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identifier VARCHAR(190) NOT NULL, ip_address VARCHAR(45) NOT NULL, successful TINYINT(1) NOT NULL DEFAULT 0, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_attempt(identifier,ip_address,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS user_profiles (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 bio TEXT NULL,
 location VARCHAR(120) NULL,
 website VARCHAR(255) NULL,
 avatar_path VARCHAR(255) NULL,
 banner_path VARCHAR(255) NULL,
 visibility ENUM('public','members','private') NOT NULL DEFAULT 'public',
 onboarding_step TINYINT UNSIGNED NOT NULL DEFAULT 1,
 onboarding_completed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS identity_types (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL UNIQUE,
 slug VARCHAR(90) NOT NULL UNIQUE,
 description VARCHAR(255) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL UNIQUE,
 slug VARCHAR(90) NOT NULL UNIQUE,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS universes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 slug VARCHAR(110) NOT NULL UNIQUE,
 icon VARCHAR(30) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_identity_types (
 user_id BIGINT UNSIGNED NOT NULL,
 identity_type_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,identity_type_id),
 CONSTRAINT fk_uit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_uit_type FOREIGN KEY(identity_type_id) REFERENCES identity_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_interests (
 user_id BIGINT UNSIGNED NOT NULL,
 interest_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,interest_id),
 CONSTRAINT fk_ui_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_ui_interest FOREIGN KEY(interest_id) REFERENCES interests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_universes (
 user_id BIGINT UNSIGNED NOT NULL,
 universe_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,universe_id),
 CONSTRAINT fk_uu_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_uu_universe FOREIGN KEY(universe_id) REFERENCES universes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_social_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 platform VARCHAR(40) NOT NULL,
 url VARCHAR(255) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 CONSTRAINT fk_social_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_social_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_preferences (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 email_newsletter TINYINT(1) NOT NULL DEFAULT 1,
 email_community_updates TINYINT(1) NOT NULL DEFAULT 1,
 email_product_updates TINYINT(1) NOT NULL DEFAULT 1,
 CONSTRAINT fk_preferences_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO identity_types(name,slug,sort_order) VALUES
('Fan','fan',10),('Creator','creator',20),('Artist','artist',30),('Writer','writer',40),('Cosplayer','cosplayer',50),('Collector','collector',60),('Vendor','vendor',70),('Publisher','publisher',80),('Content Creator','content-creator',90),('Game Developer','game-developer',100),('Convention Organizer','convention-organizer',110);

INSERT IGNORE INTO interests(name,slug,sort_order) VALUES
('Comics','comics',10),('Movies','movies',20),('Television','television',30),('Anime','anime',40),('Video Games','video-games',50),('Tabletop Games','tabletop-games',60),('Role-Playing Games','role-playing-games',70),('Cosplay','cosplay',80),('Collectibles','collectibles',90),('Art','art',100),('Books','books',110),('Podcasts','podcasts',120),('Streaming','streaming',130);

INSERT IGNORE INTO universes(name,slug,icon,sort_order) VALUES
('Marvel','marvel','⚡',10),('DC','dc','🦇',20),('Star Wars','star-wars','✨',30),('Star Trek','star-trek','🖖',40),('Doctor Who','doctor-who','🌀',50),('Dungeons & Dragons','dungeons-dragons','🐉',60),('The Lord of the Rings','lord-of-the-rings','💍',70),('Warhammer','warhammer','⚔️',80),('Pokémon','pokemon','⚡',90),('Anime','anime','🌸',100),('Horror','horror','👻',110),('Indie Comics','indie-comics','✍️',120);
CREATE TABLE IF NOT EXISTS invitations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 recipient_name VARCHAR(150) NULL,
 invitation_type ENUM('member','admin') NOT NULL DEFAULT 'member',
 assigned_role ENUM('fan','admin') NOT NULL DEFAULT 'fan',
 personal_message TEXT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 status ENUM('pending','accepted','expired','revoked') NOT NULL DEFAULT 'pending',
 invited_by BIGINT UNSIGNED NOT NULL,
 expires_at DATETIME NOT NULL,
 accepted_by BIGINT UNSIGNED NULL,
 accepted_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_invitation_sender FOREIGN KEY(invited_by) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_invitation_acceptor FOREIGN KEY(accepted_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_invitation_email(email),
 INDEX idx_invitation_status(status),
 INDEX idx_invitation_expires(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
