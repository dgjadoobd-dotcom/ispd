CREATE TABLE IF NOT EXISTS rate_limit_hits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_key (`key`(191)),
    KEY idx_window_start (window_start)
) ENGINE=InnoDB;
