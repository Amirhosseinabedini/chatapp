# Advanced Chat Application

A modern, real-time chat application built with Symfony 7, featuring comprehensive authentication, presence system, file sharing, and more.

## 🚀 Features

### Core Features (MVP → Advanced)
- ✅ **User Registration/Login** (Email/Password) + Profile Management (Name, Avatar)
- ✅ **One-to-One Chat** (Direct Messages)
- ✅ **Presence System** (Online/Offline) + Typing Indicators
- ✅ **Private/Presence Channels** for secure conversations
- ✅ **Delivery & Read Receipts** (Sent / Delivered / Read)
- ✅ **Message History & Pagination** (Load old messages with scroll up)
- ✅ **File/Image Upload** with preview and size/format limits
- ✅ **Browser Notifications** + badge in tab
- ✅ **Message Search** (Full-text search + Index on messages)

### Technical Features
- **Real-time Communication**: Mercure Hub for WebSocket-like functionality
- **Security**: CSRF protection, secure password hashing, private channels
- **File Management**: Organized upload directories, file type validation
- **Responsive Design**: Mobile-friendly Bootstrap 5 interface
- **Logging**: Comprehensive authentication and activity logging
- **Database**: PostgreSQL with Doctrine ORM

## 🛠️ Installation

### Prerequisites
- PHP 8.2+
- Composer
- PostgreSQL
- Node.js (for asset management)
- Mercure Hub (for real-time features)

### Setup

1. **Clone and Install Dependencies**
```bash
cd /Applications/MAMP/htdocs/chatapp
composer install
```

2. **Environment Configuration**
```bash
# Copy environment file
cp .env .env.local

# Edit .env.local with your database credentials
DATABASE_URL="postgresql://username:password@127.0.0.1:5432/chatapp_db"
MERCURE_PUBLISHER_JWT_KEY="your-publisher-key"
MERCURE_SUBSCRIBER_JWT_KEY="your-subscriber-key"
```

3. **Database Setup**
```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate
```

4. **Create Users**
```bash
# Create admin user
php bin/console app:create-user --email=admin@chatapp.com --password=admin123

# Create test user
php bin/console app:create-user --email=test@chatapp.com --password=test123

# List all users
php bin/console app:list-users
```

5. **Start Mercure Hub**
```bash
# Using Docker
docker run -d -p 8080:80 dunglas/mercure

# Or using the standalone binary
mercure --addr=:8080 --cors-allowed-origins=http://localhost:8000
```

6. **Start Development Server**
```bash
symfony serve
# or
php -S localhost:8000 -t public/
```

## 👥 Default Users

| Email | Password | Role |
|-------|----------|------|
| admin@chatapp.com | admin123 | ROLE_USER |
| test@chatapp.com | test123 | ROLE_USER |
| john@chatapp.com | john123 | ROLE_USER |

## 🎯 Usage

### Authentication
- Navigate to `/login` to sign in
- After login, you'll be redirected to the chat interface
- All authentication events are logged for security monitoring

### Chat Features
- **Start Conversation**: Click on any user in the sidebar
- **Send Messages**: Type in the message input and press Enter
- **File Sharing**: Click the paperclip icon to upload files
- **Typing Indicators**: See when someone is typing
- **Read Receipts**: Check marks show message status
- **Search**: Use the search box to find messages

### Profile Management
- **Edit Profile**: Click your name in the navbar → Edit Profile
- **Upload Avatar**: Choose an image file (max 2MB)
- **Display Name**: Set a custom name shown to other users

## 🔧 Commands

### User Management
```bash
# Create new user
php bin/console app:create-user --email=user@example.com --password=password123

# List all users
php bin/console app:list-users

# Update existing users with display names
php bin/console app:update-users
```

### Database
```bash
# Create migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Reset database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 📁 Project Structure

```
chatapp/
├── src/
│   ├── Controller/
│   │   ├── ChatController.php          # Main chat functionality
│   │   ├── ProfileController.php       # User profile management
│   │   ├── PresenceController.php      # Online/offline status
│   │   ├── FileUploadController.php    # File sharing
│   │   ├── SecurityController.php      # Authentication
│   │   └── MercureAuthController.php   # Real-time auth
│   ├── Entity/
│   │   ├── User.php                    # User entity with profile fields
│   │   └── Message.php                 # Message entity with file support
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   └── MessageRepository.php       # Advanced message queries
│   ├── Security/
│   │   └── LoginFormAuthenticator.php  # Custom authentication
│   ├── EventListener/
│   │   ├── LoginEventListener.php      # Login logging
│   │   ├── LoginFailureEventListener.php
│   │   └── LogoutEventListener.php
│   └── Command/
│       ├── CreateUserCommand.php       # User creation CLI
│       ├── ListUsersCommand.php
│       └── UpdateUsersCommand.php
├── templates/
│   ├── base.html.twig                  # Modern Bootstrap layout
│   ├── chat/
│   │   ├── index.html.twig             # Conversation list
│   │   └── conversation.html.twig      # Chat interface
│   ├── profile/
│   │   ├── index.html.twig             # Profile view
│   │   └── edit.html.twig              # Profile editing
│   └── security/
│       └── login.html.twig             # Login form
├── public/
│   ├── uploads/
│   │   ├── avatars/                    # User profile pictures
│   │   ├── images/                     # Shared images
│   │   ├── documents/                   # Shared documents
│   │   └── files/                      # Other files
│   └── images/
│       └── default-avatar.svg          # Default user avatar
└── assets/
    └── styles/
        └── app.css                     # Custom chat styles
```

## 🔒 Security Features

- **CSRF Protection**: All forms protected against CSRF attacks
- **Password Hashing**: Secure bcrypt/argon2i hashing
- **Private Channels**: Messages only visible to participants
- **File Validation**: Type and size restrictions on uploads
- **Authentication Logging**: All login attempts logged
- **Session Management**: Secure session handling

## 📱 Real-time Features

- **Mercure Integration**: WebSocket-like real-time communication
- **Presence System**: Live online/offline status
- **Typing Indicators**: Real-time typing notifications
- **Message Delivery**: Instant message delivery
- **Browser Notifications**: Desktop notifications for new messages

## 🎨 UI/UX Features

- **Responsive Design**: Works on desktop and mobile
- **Modern Interface**: Bootstrap 5 with custom styling
- **Dark/Light Theme**: Automatic theme detection
- **Smooth Animations**: CSS transitions and animations
- **Accessibility**: ARIA labels and keyboard navigation
- **File Previews**: Image previews and file type icons

## 🚀 Deployment

### Production Setup

1. **Environment Configuration**
```bash
# Set production environment
APP_ENV=prod
APP_DEBUG=0

# Configure database
DATABASE_URL="postgresql://user:pass@host:5432/dbname"

# Configure Mercure
MERCURE_PUBLISHER_JWT_KEY="your-production-publisher-key"
MERCURE_SUBSCRIBER_JWT_KEY="your-production-subscriber-key"
```

2. **Asset Compilation**
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
php bin/console cache:clear --env=prod
```

3. **Web Server Configuration**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/chatapp/public;
    
    location / {
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
    
    location ~ \.php$ {
        return 404;
    }
}
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Support

For support and questions:
- Check the logs in `var/log/`
- Review the authentication logs for login issues
- Ensure Mercure Hub is running for real-time features
- Verify database connections and migrations

## 🔄 Updates

To update the application:
```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer update

# Run new migrations
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear
```

