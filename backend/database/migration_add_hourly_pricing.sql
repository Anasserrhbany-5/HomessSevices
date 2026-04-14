USE home_services;
ALTER TABLE services ADD COLUMN is_hourly BOOLEAN DEFAULT TRUE AFTER price;
UPDATE services SET is_hourly = TRUE WHERE is_hourly IS NULL;