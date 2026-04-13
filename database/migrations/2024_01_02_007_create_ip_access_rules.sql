CREATE TABLE IF NOT EXISTS ip_access_rules (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cidr VARCHAR(50) NOT NULL,
    type ENUM('whitelist','blacklist') NOT NULL DEFAULT 'whitelist',
    comment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_type (type)
) ENGINE=InnoDB;
