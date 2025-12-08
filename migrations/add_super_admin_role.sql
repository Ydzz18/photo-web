-- Migration: Add super admin role to admins table
-- This migration adds the is_super_admin column to track which admins have super admin privileges

-- Add is_super_admin column if it doesn't exist
ALTER TABLE admins ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER password;

-- Create index on is_super_admin for faster queries
CREATE INDEX idx_is_super_admin ON admins(is_super_admin);

-- Set the first admin (by creation date) as super admin
UPDATE admins SET is_super_admin = 1 WHERE id = (SELECT id FROM admins ORDER BY created_at ASC LIMIT 1);

-- Verify changes
SELECT id, username, email, is_super_admin, created_at FROM admins ORDER BY is_super_admin DESC, created_at ASC;
