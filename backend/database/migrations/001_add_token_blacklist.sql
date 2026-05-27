USE task_management;

CREATE TABLE IF NOT EXISTS token_blacklist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_jti VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_blacklist_jti (token_jti),
    INDEX idx_blacklist_expires (expires_at)
) ENGINE=InnoDB;
