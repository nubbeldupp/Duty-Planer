# On-Call Duty Planner

## Overview
On-Call Duty Planner is a comprehensive web application designed to manage and streamline on-call schedules for database teams across multiple organizations.

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

## 🚀 Technical Architecture

### Backend
- Language: PHP 8.1+
- Framework: Custom MVC Architecture
- Database: Oracle with OCI8
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

## 📦 Installation

### Prerequisites
- Docker
- Docker Compose
- PHP 8.1+
- Composer

### Steps
1. Clone the repository
   ```bash
   git clone https://github.com/yourusername/on-call-duty-planner.git
   cd on-call-duty-planner
   ```

2. Copy environment configuration
   ```bash
   cp .env.example .env
   ```

3. Configure environment variables in `.env`

4. Build and start containers
   ```bash
   ./deploy.sh
   ```

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
3. Commit changes
4. Push to the branch
5. Create a pull request

## 📄 License
MIT License

## 📧 Contact
For support, email: support@oncalldutyplanner.com
