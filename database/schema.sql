-- Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('ADMIN', 'TEAM_LEAD', 'USER') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Database Teams Table
CREATE TABLE database_teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255)
);

-- User-Team Mapping Table (Many-to-Many Relationship)
CREATE TABLE user_teams (
    user_id INT,
    team_id INT,
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (team_id) REFERENCES database_teams(team_id)
);

-- On-Call Duty Schedule Table
CREATE TABLE on_call_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    team_id INT NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    duty_type ENUM('PERMANENT', 'OCCASIONALLY') NOT NULL,
    status ENUM('ACTIVE', 'PENDING', 'INACTIVE') DEFAULT 'ACTIVE',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (team_id) REFERENCES database_teams(team_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Schedule Change Requests Table
CREATE TABLE schedule_change_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    original_schedule_id INT NOT NULL,
    requested_user_id INT NOT NULL,
    target_user_id INT NOT NULL,
    new_start_datetime DATETIME,
    new_end_datetime DATETIME,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    request_reason VARCHAR(255),
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (original_schedule_id) REFERENCES on_call_schedules(schedule_id),
    FOREIGN KEY (requested_user_id) REFERENCES users(user_id),
    FOREIGN KEY (target_user_id) REFERENCES users(user_id)
);

-- Initial Data Insertion
-- Insert Database Teams
INSERT INTO database_teams (team_name, description) VALUES 
('Oracle', 'Oracle Database Team'),
('Hana', 'SAP Hana Database Team'),
('MSSQL', 'Microsoft SQL Server Team'),
('PostgreSQL', 'PostgreSQL Database Team');

-- Create Indexes for Performance
CREATE INDEX idx_user_teams_user_id ON user_teams(user_id);
CREATE INDEX idx_user_teams_team_id ON user_teams(team_id);
CREATE INDEX idx_schedules_user_id ON on_call_schedules(user_id);
CREATE INDEX idx_schedules_team_id ON on_call_schedules(team_id);
