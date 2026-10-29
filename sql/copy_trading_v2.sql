-- Apply once after copy_trading.sql. In production take a database backup first.
ALTER TABLE copy_simulated_events
 ADD COLUMN client_event_id VARCHAR(80) NULL AFTER trader_id,
 ADD UNIQUE KEY unique_master_event (trader_id, client_event_id);
-- Keep old events nullable; newly published events always specify an idempotency key.
