-- Database: voting_system

CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

-- Users table
CREATE TABLE IF NOT EXISTS Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('voter', 'admin') DEFAULT 'voter',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Elections table
CREATE TABLE IF NOT EXISTS Elections (
    election_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATETIME,
    end_date DATETIME,
    status ENUM('upcoming', 'ongoing', 'ended') DEFAULT 'upcoming'
);

-- Candidates table
CREATE TABLE IF NOT EXISTS Candidates (
    candidate_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    election_id INT,
    FOREIGN KEY (election_id) REFERENCES Elections(election_id)
);

-- Votes table
CREATE TABLE IF NOT EXISTS Votes (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    candidate_id INT,
    election_id INT,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (candidate_id) REFERENCES Candidates(candidate_id),
    FOREIGN KEY (election_id) REFERENCES Elections(election_id),
    UNIQUE(user_id, election_id)  -- prevents double voting
);

-- AdminLogs table
CREATE TABLE IF NOT EXISTS AdminLogs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES Users(user_id)
);
