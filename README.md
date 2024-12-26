# On-Call Duty Planner

## Overview
On-Call Duty Planner is a comprehensive web application designed to streamline and manage on-call schedules for database teams across various technologies including Oracle, Hana, MSSQL, and PostgreSQL.

## Recent Changes

### Docker Configuration Updates
- Simplified Docker container setup
- Updated nginx configuration to route PHP requests
- Refined database connection handling
- Modified backup volume to `/mnt/datadisk/backup`

## 🌟 Key Features

### User Roles
- **Admin**: Full system control
  - Add/manage users
  - Assign teams
  - Global schedule oversight

- **Team Lead**: Team-specific management
  - Edit team schedules
  - Manage team members
  - Approve schedule changes

- **User**: Personal schedule management
  - View and edit personal schedules
  - Request schedule changes

### On-Call Duty Types
- **Permanent**: Recurring schedules
- **Occasionally**: Flexible, ad-hoc assignments

### Supported Database Teams
- Oracle
- Hana
- MSSQL
- PostgreSQL

## Technology Stack

- **Backend**: PHP 8.1+
- **Database**: MySQL 8.0
- **Containerization**: Docker Compose
- **Email**: PHPMailer
- **Authentication**: Custom implementation with Argon2 password hashing
- **Dependency Management**: Composer

## 🚀 Technical Architecture

### Backend
- Language: PHP 8.1+
- Framework: Custom MVC Architecture
- Database: MySQL with PDO
- Authentication: JWT-based

### Frontend
- HTML5
- CSS3 (Modern Design)
- JavaScript (ES6+)
- FullCalendar for Schedule Visualization

### Infrastructure
- Docker containerization
- PostgreSQL database
- Redis caching
- GitHub Actions CI/CD

## 🔐 Security Features
- Role-based access control
- Secure password hashing (Argon2)
- Email notification system
- Comprehensive error handling
- Audit logging

## Prerequisites

- Docker
- Docker Compose
- Minimum 2GB RAM
- Network access to pull Docker images

## 📦 Installation

### Steps
1. Clone the repository
   ```bash
   git clone https://github.com/nubbeldupp/Duty-Planer
   cd Duty-Planer
   ```

2. Start the application
   ```bash
   docker-compose up --build
   ```

3. Access the application
   - Web Application: `http://localhost`
   - Database Backup Location: `/mnt/datadisk/backup`

## Configuration

### Environment Variables
- `DB_HOST`: Database host (default: `database`)
- `DB_PORT`: Database port (default: `3306`)
- `DB_NAME`: Database name (default: `on_call_duty_planner`)
- `DB_USER`: Database user (default: `app_user`)
- `DB_PASSWORD`: Database password

### Backup Configuration
- Backup frequency: Daily
- Backup location: `/mnt/datadisk/backup`

## Troubleshooting

- Ensure Docker and Docker Compose are installed
- Check container logs with `docker-compose logs`
- Verify network connectivity
- Confirm database credentials match in configuration files

## Security

- Passwords hashed using Argon2
- Role-based access control
- Secure database connection
- Environment-specific configurations

## 🛠 Development

### Running Tests
```bash
composer test
```

### Database Migrations
```bash
docker-compose exec web php artisan migrate
```

## 🌈 Color Palette
- White: #FFFFFFff
- Midnight Green: #064B57ff
- Khaki: #BBA389ff
- Yellow Green: #B5CC32ff
- Ash Gray: #B2BAB5ff

## 📋 Workflow
1. Users log in
2. View personal/team schedules
3. Request schedule changes
4. Receive email notifications
5. Approve/reject changes

## 🔍 Upcoming Features
- Mobile responsiveness
- Advanced reporting
- Integration with external calendars

## 🤝 Contributing
1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

