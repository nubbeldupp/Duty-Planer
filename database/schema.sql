-- Users Table
CREATE TABLE users (
    user_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username VARCHAR2(50) UNIQUE NOT NULL,
    email VARCHAR2(100) UNIQUE NOT NULL,
    password_hash VARCHAR2(255) NOT NULL,
    first_name VARCHAR2(50) NOT NULL,
    last_name VARCHAR2(50) NOT NULL,
    role ENUM('ADMIN', 'TEAM_LEAD', 'USER') NOT NULL,
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    last_login TIMESTAMP
);

-- Database Teams Table
CREATE TABLE database_teams (
    team_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    team_name VARCHAR2(50) UNIQUE NOT NULL,
    description VARCHAR2(255)
);

-- User-Team Mapping Table (Many-to-Many Relationship)
CREATE TABLE user_teams (
    user_id NUMBER,
    team_id NUMBER,
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (team_id) REFERENCES database_teams(team_id)
);

-- On-Call Duty Schedule Table
CREATE TABLE on_call_schedules (
    schedule_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id NUMBER NOT NULL,
    team_id NUMBER NOT NULL,
    start_datetime TIMESTAMP NOT NULL,
    end_datetime TIMESTAMP NOT NULL,
    duty_type ENUM('PERMANENT', 'OCCASIONALLY') NOT NULL,
    status ENUM('ACTIVE', 'PENDING', 'INACTIVE') DEFAULT 'ACTIVE',
    created_by NUMBER NOT NULL,
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (team_id) REFERENCES database_teams(team_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Schedule Change Requests Table
CREATE TABLE schedule_change_requests (
    request_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    original_schedule_id NUMBER NOT NULL,
    requested_user_id NUMBER NOT NULL,
    target_user_id NUMBER NOT NULL,
    new_start_datetime TIMESTAMP,
    new_end_datetime TIMESTAMP,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    request_reason VARCHAR2(255),
    requested_at TIMESTAMP DEFAULT SYSTIMESTAMP,
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
