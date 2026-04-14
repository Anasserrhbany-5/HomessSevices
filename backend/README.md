# Home Services Management Backend

A REST API backend for a Home Services Management web application built with pure PHP and MySQL.

## Features

- User authentication with JWT
- Role-based access control (Client, Worker, Admin)
- Service management
- Request and job management
- Chat functionality
- Review system

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx with mod_rewrite (for clean URLs)

## Installation

1. Clone or download the project.
2. Set up the database:
   - Create a MySQL database named `home_services`.
   - Run the SQL script in `database/schema.sql` to create tables.
3. Configure database connection in `config/database.php`.
4. Update JWT secret in `utils/jwt.php`.
5. Serve the `backend/` directory with your web server, ensuring `index.php` is the entry point.

## API Endpoints

### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login user

### Client
- `POST /api/requests` - Create service request
- `GET /api/requests` - Get client's requests
- `GET /api/requests/{id}` - Get specific request
- `POST /api/reviews` - Create review

### Worker
- `GET /api/jobs/available` - Get available jobs
- `POST /api/jobs/accept/{id}` - Accept job
- `POST /api/jobs/reject/{id}` - Reject job
- `GET /api/jobs/assigned` - Get assigned jobs
- `PUT /api/jobs/status/{id}` - Update job status

### Admin
- `GET /api/users` - Get all users
- `DELETE /api/users/{id}` - Delete user
- `GET /api/services` - Get all services
- `POST /api/services` - Create service
- `PUT /api/services/{id}` - Update service
- `DELETE /api/services/{id}` - Delete service
- `GET /api/admin/requests` - Get all requests

### Chat
- `POST /api/messages` - Send message
- `GET /api/messages/{userId}` - Get messages with user

## Usage

All requests except register and login require an Authorization header: `Bearer <token>`

Send JSON data in request body for POST/PUT requests.

## Security

- Passwords are hashed with bcrypt
- JWT tokens for authentication
- PDO prepared statements for SQL queries
- Input validation and sanitization
- CORS support

## Project Structure

```
backend/
├── config/
│   └── database.php
├── controllers/
│   ├── AuthController.php
│   ├── ClientController.php
│   ├── WorkerController.php
│   ├── AdminController.php
│   └── ChatController.php
├── database/
│   └── schema.sql
├── middleware/
│   ├── auth.php
│   └── cors.php
├── models/
│   ├── User.php
│   ├── Service.php
│   ├── Request.php
│   ├── Job.php
│   ├── Message.php
│   └── Review.php
├── utils/
│   ├── jwt.php
│   └── validation.php
├── index.php
└── README.md
```