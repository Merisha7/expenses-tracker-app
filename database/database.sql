-- Expense Tracker Database

CREATE DATABASE IF NOT EXISTS expenses_tracker
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE expenses_tracker;

CREATE TABLE IF NOT EXISTS users (
	user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(191) NOT NULL UNIQUE,
	password VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
	expenses_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	amount DECIMAL(10,2) NOT NULL,
	description TEXT,
	date DATE NOT NULL,
	CONSTRAINT fk_expenses_user
		FOREIGN KEY (user_id)
		REFERENCES users(user_id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	INDEX idx_expenses_user_date (user_id, date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS income (
	income_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	amount DECIMAL(10,2) NOT NULL,
	source VARCHAR(100) NOT NULL,
	description TEXT,
	date DATE NOT NULL,
	CONSTRAINT fk_income_user
		FOREIGN KEY (user_id)
		REFERENCES users(user_id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	INDEX idx_income_user_date (user_id, date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS savings_goals (
	goal_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	goal_name VARCHAR(100) NOT NULL,
	required_amount DECIMAL(12,2) NOT NULL,
	current_savings DECIMAL(12,2) NOT NULL DEFAULT 0,
	total_budget DECIMAL(12,2) NOT NULL,
	start_date DATE NOT NULL,
	due_date DATE NOT NULL,
	is_achieved TINYINT(1) NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_savings_goals_user
		FOREIGN KEY (user_id)
		REFERENCES users(user_id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	INDEX idx_goals_user_date (user_id, due_date)
) ENGINE=InnoDB;
