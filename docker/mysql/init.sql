-- Set UTF8MB4 encoding for proper Croatian character support
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS abbrevio 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE abbrevio;

-- Ensure all new tables will use UTF8MB4 by default
ALTER DATABASE abbrevio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Test data will be inserted via Laravel seeders
