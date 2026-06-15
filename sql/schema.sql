-- SQL schema for Pageant Validation System
CREATE DATABASE IF NOT EXISTS pageant_db;
USE pageant_db;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) DEFAULT 'Coordinator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS judges (
    judge_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contestants (
    contestant_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    age INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS criteria (
    criteria_id INT AUTO_INCREMENT PRIMARY KEY,
    criteria_name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL CHECK (percentage >= 0 and percentage <= 100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scores (
    score_id INT AUTO_INCREMENT PRIMARY KEY,
    judge_id INT NOT NULL,
    contestant_id INT NOT NULL,
    criteria_id INT NOT NULL,
    score DECIMAL(6,2) NOT NULL CHECK (score >= 0 and score <= 100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (judge_id, contestant_id, criteria_id),
    FOREIGN KEY (judge_id) REFERENCES judges(judge_id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(contestant_id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(criteria_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    contestant_id INT NOT NULL,
    total_score DECIMAL(6,2),
    rank INT,
    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contestant_id) REFERENCES contestants(contestant_id) ON DELETE CASCADE
);
