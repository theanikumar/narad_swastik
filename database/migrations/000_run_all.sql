-- NARAD-SWASTIK Database Schema
-- Run order is critical due to foreign key dependencies

SOURCE 001_create_roles_table.sql;
SOURCE 002_create_users_table.sql;
SOURCE 003_create_attendance_table.sql;
SOURCE 004_create_vehicles_table.sql;
SOURCE 005_create_materials_table.sql;
SOURCE 006_create_shifts_table.sql;
SOURCE 007_create_trips_table.sql;
SOURCE 008_create_breakdowns_table.sql;
SOURCE 009_create_locations_table.sql;
SOURCE 010_create_audit_logs_table.sql;
SOURCE 011_add_indexes.sql;
