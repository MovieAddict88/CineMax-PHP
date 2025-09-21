<?php
require_once 'config.php';

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select database
$conn->select_db(DB_NAME);

// SQL to create tables
$sql = "
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS `subcategories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS `entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT,
    `subcategory_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `country` VARCHAR(255),
    `description` TEXT,
    `poster` VARCHAR(255),
    `thumbnail` VARCHAR(255),
    `rating` FLOAT,
    `duration` VARCHAR(50),
    `year` INT,
    `parental_rating` VARCHAR(50),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
    FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`)
);

CREATE TABLE IF NOT EXISTS `seasons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entry_id` INT,
    `season_number` INT,
    `poster` VARCHAR(255),
    FOREIGN KEY (`entry_id`) REFERENCES `entries`(`id`)
);

CREATE TABLE IF NOT EXISTS `episodes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `season_id` INT,
    `episode_number` INT,
    `title` VARCHAR(255),
    `duration` VARCHAR(50),
    `description` TEXT,
    `thumbnail` VARCHAR(255),
    FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`)
);

CREATE TABLE IF NOT EXISTS `servers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entry_id` INT,
    `episode_id` INT,
    `name` VARCHAR(255),
    `url` VARCHAR(255),
    `license` VARCHAR(255),
    `drm` BOOLEAN,
    FOREIGN KEY (`entry_id`) REFERENCES `entries`(`id`),
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`)
);
";

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "Tables created successfully\n";
} else {
    echo "Error creating tables: " . $conn->error . "\n";
}

$conn->close();
?>
