-- MySQL syntax
CREATE DATABASE IF NOT EXISTS api;
USE api;

CREATE TABLE IF NOT EXISTS apps (
	id INTEGER UNIQUE KEY AUTO_INCREMENT, -- internal id
	app_id VARCHAR(20) PRIMARY KEY, -- universal id
	app_short_name VARCHAR(50) NOT NULL,
	app_long_name VARCHAR(100) NOT NULL,
	app_description VARCHAR(255),
	app_version VARCHAR(20) NOT NULL,
	app_build_date DATETIME NOT NULL,
	app_install_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
	id INTEGER UNIQUE KEY AUTO_INCREMENT, -- internal id
	user_id VARCHAR(20) PRIMARY KEY, -- universal id
	user_name VARCHAR(100) NOT NULL,
	user_email VARCHAR (100),
	user_address VARCHAR (500),
	user_bio VARCHAR(500),
	user_picture_path VARCHAR (100),
	user_ip VARCHAR(15) NOT NULL,
	movein_date DATE,
	known_date DATETIME NOT NULL,
	join_date DATETIME NOT NULL,
	retrieval_date TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vouch (
	id INTEGER PRIMARY KEY AUTO_INCREMENT, -- internal id
	user_id VARCHAR(20) UNIQUE KEY,
	status ENUM('notvouch', 'waiting', 'vouched') NOT NULL DEFAULT "notvouch",
	timestamp TIMESTAMP NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS vouch_code (
	id INTEGER PRIMARY KEY AUTO_INCREMENT, -- internal id
	user_id VARCHAR(20) UNIQUE KEY,
	code VARCHAR(100), 
	timestamp TIMESTAMP NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS users_me (
	id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	user_id VARCHAR(20),
	user_name VARCHAR(100) NOT NULL,
	user_email VARCHAR (100),
	user_address VARCHAR (500),
	user_bio VARCHAR(500),
	user_picture_path VARCHAR(100),
	movein_date DATE,
	join_date DATETIME NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS users_me_password (
	id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	user_id VARCHAR(20) UNIQUE KEY,
	hash VARCHAR(500),
	FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS apps_message (
	id INTEGER PRIMARY KEY AUTO_INCREMENT, -- internal id
	app_id VARCHAR(20) NOT NULL,
	author_id VARCHAR(20) NOT NULL, 
	message VARCHAR(1000),
	timestamp TIMESTAMP NOT NULL,
	FOREIGN KEY (app_id) REFERENCES apps(app_id),
	FOREIGN KEY (author_id) REFERENCES users(user_id)
);

-- use this table for specifying recipients of private messages
CREATE TABLE IF NOT EXISTS apps_message_recipient (
	message_id INTEGER NOT NULL,
	recipient_id VARCHAR(20) NOT NULL,
	FOREIGN KEY (message_id) REFERENCES apps_message(id),
	FOREIGN KEY (recipient_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS following_users (
	id INTEGER PRIMARY KEY AUTO_INCREMENT,
	app_id VARCHAR(20) NOT NULL,
	following_id VARCHAR(20) NOT NULL,
	FOREIGN KEY (app_id) REFERENCES apps(app_id),
	FOREIGN KEY (following_id) REFERENCES users(user_id),
	UNIQUE (app_id, following_id)
);

CREATE TABLE IF NOT EXISTS follower_users (
	id INTEGER PRIMARY KEY AUTO_INCREMENT,
	app_id VARCHAR(20) NOT NULL,
	follower_id VARCHAR(20) NOT NULL,
	FOREIGN KEY (app_id) REFERENCES apps(app_id),
	FOREIGN KEY (follower_id) REFERENCES users(user_id),
	UNIQUE (app_id, follower_id)
);