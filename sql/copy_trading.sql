-- Copy trading simulation for Katsuki Trading.
-- Apply once after sql/schema.sql. This migration does not enable broker orders.
CREATE TABLE IF NOT EXISTS copy_traders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    bio VARCHAR(500) NOT NULL DEFAULT '',
    status ENUM('pending', 'active', 'paused', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_copy_traders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_copy_traders_status (status, created_at)
);

CREATE TABLE IF NOT EXISTS copy_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_user_id INT NOT NULL,
    trader_id BIGINT UNSIGNED NOT NULL,
    allocation_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    max_loss_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    status ENUM('active', 'paused') NOT NULL DEFAULT 'paused',
    consent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_copy_follower_trader UNIQUE (follower_user_id, trader_id),
    CONSTRAINT fk_copy_subscription_follower FOREIGN KEY (follower_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_copy_subscription_trader FOREIGN KEY (trader_id) REFERENCES copy_traders(id) ON DELETE CASCADE,
    INDEX idx_copy_subscriptions_trader_status (trader_id, status),
    CHECK (allocation_percent > 0 AND allocation_percent <= 100),
    CHECK (max_loss_percent > 0 AND max_loss_percent <= 100)
);

CREATE TABLE IF NOT EXISTS copy_signals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trader_id BIGINT UNSIGNED NOT NULL,
    client_event_id VARCHAR(80) NOT NULL,
    position_ref VARCHAR(80) NOT NULL,
    event_type ENUM('open', 'modify', 'close') NOT NULL,
    symbol VARCHAR(30) NOT NULL,
    direction ENUM('buy', 'sell') NOT NULL,
    entry_price DECIMAL(20,8) NULL,
    stop_loss DECIMAL(20,8) NULL,
    take_profit DECIMAL(20,8) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_copy_signal_event UNIQUE (trader_id, client_event_id),
    CONSTRAINT fk_copy_signal_trader FOREIGN KEY (trader_id) REFERENCES copy_traders(id) ON DELETE CASCADE,
    INDEX idx_copy_signals_position (trader_id, position_ref, id),
    INDEX idx_copy_signals_created (created_at)
);
