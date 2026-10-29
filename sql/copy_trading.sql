-- Run once AFTER sql/schema.sql. This is a simulation-only module.
CREATE TABLE IF NOT EXISTS copy_traders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  bio VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS copy_subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  follower_user_id INT NOT NULL,
  trader_id BIGINT UNSIGNED NOT NULL,
  allocation_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  max_loss_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  status ENUM('active','paused') NOT NULL DEFAULT 'paused',
  consent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_follower_trader (follower_user_id,trader_id),
  INDEX idx_copy_subscriptions_trader (trader_id,status),
  FOREIGN KEY (follower_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (trader_id) REFERENCES copy_traders(id) ON DELETE CASCADE,
  CHECK (allocation_percent > 0 AND allocation_percent <= 100),
  CHECK (max_loss_percent > 0 AND max_loss_percent <= 100)
);
CREATE TABLE IF NOT EXISTS copy_simulated_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  trader_id BIGINT UNSIGNED NOT NULL,
  symbol VARCHAR(30) NOT NULL,
  direction ENUM('buy','sell') NOT NULL,
  entry_price DECIMAL(20,8) NOT NULL,
  stop_loss DECIMAL(20,8) NOT NULL,
  take_profit DECIMAL(20,8) NOT NULL,
  status ENUM('simulated') NOT NULL DEFAULT 'simulated',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (trader_id) REFERENCES copy_traders(id) ON DELETE CASCADE
);
