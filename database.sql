-- بديل لـ install.php: استورده من phpMyAdmin داخل قاعدة بياناتك الفارغة
CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY, reg_no VARCHAR(20) UNIQUE, full_name VARCHAR(150) NOT NULL, name_key VARCHAR(190) NULL,
  nickname VARCHAR(80), gender ENUM('ذكر','أنثى') NOT NULL, age TINYINT UNSIGNED, phone VARCHAR(25) NOT NULL UNIQUE, phone2 VARCHAR(25),
  job VARCHAR(100), education VARCHAR(50), location_type ENUM('inside','outside') NOT NULL, country VARCHAR(80) NOT NULL,
  state VARCHAR(80), locality VARCHAR(80), city VARCHAR(80) NOT NULL, area VARCHAR(120),
  join_initiatives TINYINT(1) DEFAULT 0, join_groups TINYINT(1) DEFAULT 0, consent TINYINT(1) NOT NULL DEFAULT 1,
  edit_code_hash VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX (country), INDEX (state), INDEX (city), INDEX (full_name), INDEX idx_name_key (name_key)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'registrar', active TINYINT(1) NOT NULL DEFAULT 1,
  token_version INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY, ip VARCHAR(45) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (ip)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(50) PRIMARY KEY, svalue VARCHAR(255) NOT NULL)
 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS news (
  id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, category VARCHAR(20) NOT NULL DEFAULT 'news',
  body TEXT NOT NULL, image VARCHAR(60) NULL, event_date DATE NULL, published TINYINT(1) NOT NULL DEFAULT 1,
  author_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX (published, created_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
