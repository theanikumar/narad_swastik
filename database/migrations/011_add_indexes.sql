-- Performance indexes for frequently queried columns

ALTER TABLE users ADD INDEX idx_users_role (role_id);
ALTER TABLE users ADD INDEX idx_users_email (email);
ALTER TABLE users ADD INDEX idx_users_status (status);

ALTER TABLE attendance ADD INDEX idx_attendance_user_date (user_id, date);
ALTER TABLE attendance ADD INDEX idx_attendance_date (date);

ALTER TABLE trips ADD INDEX idx_trips_supervisor_date (supervisor_id, trip_date);
ALTER TABLE trips ADD INDEX idx_trips_date (trip_date);
ALTER TABLE trips ADD INDEX idx_trips_vehicle (vehicle_id);
ALTER TABLE trips ADD INDEX idx_trips_shift (shift_id);
ALTER TABLE trips ADD INDEX idx_trips_created (created_at);

ALTER TABLE breakdowns ADD INDEX idx_breakdowns_status (status);
ALTER TABLE breakdowns ADD INDEX idx_breakdowns_vehicle (vehicle_id);
ALTER TABLE breakdowns ADD INDEX idx_breakdowns_reported (reported_by);
ALTER TABLE breakdowns ADD INDEX idx_breakdowns_created (created_at);

ALTER TABLE locations ADD INDEX idx_locations_vehicle_recorded (vehicle_id, recorded_at);
ALTER TABLE locations ADD INDEX idx_locations_recorded (recorded_at);

ALTER TABLE audit_logs ADD INDEX idx_audit_user (user_id);
ALTER TABLE audit_logs ADD INDEX idx_audit_action (action);
ALTER TABLE audit_logs ADD INDEX idx_audit_entity (entity_type, entity_id);
ALTER TABLE audit_logs ADD INDEX idx_audit_created (created_at);
