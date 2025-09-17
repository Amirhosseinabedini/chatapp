# Chat Application Authentication System

## Overview
This chat application uses Symfony's security component for user authentication with comprehensive logging.

## Features
- User login/logout with email and password
- Comprehensive logging of all authentication events
- Remember me functionality
- CSRF protection
- Password hashing with Symfony's auto hasher

## Created Users

### Admin User
- **Email**: `admin@chatapp.com`
- **Password**: `admin123`
- **Role**: `ROLE_USER`

### Test User
- **Email**: `test@chatapp.com`
- **Password**: `test123`
- **Role**: `ROLE_USER`

## Logging Features

The application logs the following authentication events:

### Successful Login
- User email/ID
- Client IP address
- User agent
- Timestamp
- Session ID

### Failed Login Attempts
- Attempted email
- Client IP address
- User agent
- Error message and code
- Timestamp
- Session ID

### User Logout
- User email/ID
- Client IP address
- User agent
- Timestamp
- Session ID

### Login Page Access
- Last username attempted
- Error status
- Error message (if any)
- Timestamp

## Commands

### Create New User
```bash
php bin/console app:create-user --email=user@example.com --password=password123
```

### List All Users
```bash
php bin/console app:list-users
```

## Security Configuration

- Password hashing: Auto (uses bcrypt or argon2i based on PHP version)
- CSRF protection: Enabled
- Remember me: Enabled (7 days lifetime)
- Session-based authentication
- Custom authenticator: `App\Security\LoginFormAuthenticator`

## Log Files

Authentication logs are written to:
- Development: `var/log/dev.log`
- Production: `var/log/prod.log`

## Routes

- Login: `/login`
- Logout: `/logout`
- After successful login: `/realtime` (chat page)

## Event Listeners

The following event listeners handle authentication logging:

1. `App\EventListener\LoginEventListener` - Logs successful logins
2. `App\EventListener\LoginFailureEventListener` - Logs failed login attempts
3. `App\EventListener\LogoutEventListener` - Logs user logouts

## Debugging

To debug authentication issues:

1. Check the log files for detailed information
2. Use Symfony's profiler (in dev environment)
3. Check database for user records
4. Verify password hashing is working correctly

## Security Notes

- All passwords are hashed using Symfony's secure password hasher
- CSRF tokens are required for login forms
- Session data is properly serialized without password hashes
- Client IP and User Agent are logged for security monitoring
