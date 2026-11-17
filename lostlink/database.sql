CREATE DATABASE IF NOT EXISTS lostlink;
USE lostlink;

DROP TABLE IF EXISTS items;
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NOT NULL,
  description TEXT,
  location_found VARCHAR(150),
  date_found DATE,
  photo VARCHAR(255) DEFAULT '',
  finder_name VARCHAR(100),
  finder_contact VARCHAR(100),
  status VARCHAR(20) NOT NULL DEFAULT 'Available',
  claimant_name VARCHAR(100) DEFAULT '',
  claimant_id VARCHAR(50) DEFAULT '',
  claim_proof TEXT,
  date_released DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO items (item_name, category, description, location_found, date_found, finder_name, finder_contact, status, claimant_name, claimant_id, claim_proof, date_released) VALUES
('Black Leather Wallet', 'Wallet', 'Black bifold wallet with a few cards inside. Owner must describe the contents.', 'Canteen, near the cashier', '2026-11-03', 'Maria Santos', '09171234567', 'Available', '', '', '', NULL),
('Student ID (BSIT)', 'ID / Cards', 'University student ID with a blue lanyard.', 'College of Computer Studies hallway', '2026-11-04', 'Jose Ramirez', '09181234567', 'Available', '', '', '', NULL),
('Samsung Phone (Blue Case)', 'Phone', 'Samsung phone with a blue silicone case and cracked corner.', 'Library, 2nd floor', '2026-11-05', 'Anna Lim', 'anna.lim@email.com', 'Claimed', 'Kevin Dela Cruz', '2023-10458', 'Described lock screen wallpaper and unlocked phone in front of CSU.', NULL),
('White USB-C Charger', 'Charger / Electronics', 'Original white 25W charger with cable.', 'Room 304', '2026-11-06', 'Prof. Reyes', 'Faculty Room', 'Available', '', '', '', NULL),
('Gray Backpack', 'Bag', 'Gray backpack with a keychain of a cat. Contains notebooks.', 'Gym bleachers', '2026-11-07', 'Mark Tan', '09191234567', 'Available', '', '', '', NULL),
('Set of Keys', 'Keys', 'Three keys on a red ring with a small tag.', 'Parking area', '2026-11-08', 'Security Guard Cruz', 'CSU Office', 'Returned', 'Liza Mendoza', '2022-09876', 'Identified key tag and the house key count.', '2026-11-09 10:30:00'),
('Scientific Calculator', 'Books / School Supplies', 'Casio fx-991 with initials written at the back.', 'Room 210', '2026-11-10', 'Paolo Garcia', '09201234567', 'Available', '', '', '', NULL),
('Prescription Eyeglasses', 'Accessories', 'Black-framed eyeglasses in a brown case.', 'Chapel', '2026-11-12', 'Sister Elena', 'Chapel Office', 'Available', '', '', '', NULL);
