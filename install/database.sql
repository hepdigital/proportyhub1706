CREATE DATABASE IF NOT EXISTS property_hub;
USE property_hub;

CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    wp_site_url VARCHAR(255),
    api_key VARCHAR(255),
    sync_type ENUM('wordpress', 'ical') DEFAULT 'ical',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit_number INT DEFAULT 1,
    ical_url TEXT,
    wp_room_id INT,
    last_sync DATETIME,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    date DATE NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    reservation_id VARCHAR(100),
    sync_source ENUM('ical', 'wordpress', 'manual') DEFAULT 'ical',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    UNIQUE KEY unique_unit_date (unit_id, date)
);

CREATE TABLE sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    unit_id INT,
    sync_type VARCHAR(50),
    status ENUM('success', 'error', 'warning') DEFAULT 'success',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Örnek veri
INSERT INTO properties (name, sync_type) VALUES 
('Demo Villa', 'ical'),
('Test Bungalov', 'ical');

INSERT INTO units (property_id, name, unit_number, ical_url) VALUES 
(1, 'Villa Unit 1', 1, 'https://example.com/villa1.ics'),
(1, 'Villa Unit 2', 2, 'https://example.com/villa2.ics'),
(2, 'Bungalov Unit 1', 1, 'https://example.com/bungalov1.ics');