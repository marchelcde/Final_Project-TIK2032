-- Migration script to add fullName column to users table
-- Run this if your existing database still uses 'name' column instead of 'fullName'

USE `e-lapor`;

-- Step 1: Add the new fullName column
ALTER TABLE `users` 
ADD COLUMN `fullName` VARCHAR(255) NOT NULL AFTER `id`;

-- Step 2: Copy data from 'name' column to 'fullName' column (if 'name' column exists)
-- This is only needed if you're migrating from the old structure
UPDATE `users` 
SET `fullName` = `name` 
WHERE `name` IS NOT NULL AND `name` != '';

-- Step 3: Drop the old 'name' column (optional - only if migrating from old structure)
-- Uncomment the line below if you want to remove the old 'name' column
-- ALTER TABLE `users` DROP COLUMN `name`;

-- Verify the changes
SELECT * FROM `users` LIMIT 5;
