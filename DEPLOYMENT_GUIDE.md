# 🚀 Production Deployment Guide

## Prerequisites
- PHP >= 8.2 with required extensions (ctype, iconv, pdo, pdo_mysql)
- Composer
- MySQL or PostgreSQL database
- Web server (Apache/Nginx)
- Git access to your repository

## Step-by-Step Deployment

### 1. Connect to Your Server
```bash
ssh your-username@your-server-ip
```

### 2. Navigate to Web Directory
```bash
cd /path/to/your/web/directory
# Example: cd /var/www/html or cd /home/username/public_html
```

### 3. Clone Repository from GitHub
```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git chatapp
cd chatapp
```

### 4. Install Dependencies
```bash
# Install Composer dependencies (production only, no dev packages)
composer install --no-dev --optimize-autoloader
```

### 5. Create Environment Configuration
```bash
# Copy the example env file
cp .env.example .env

# Edit the .env file with your actual configuration
nano .env
# or
vi .env
```

**Important: Update these values in .env:**
- `APP_SECRET`: Generate a secure random string (32+ characters)
- `DATABASE_URL`: Your actual database credentials
- `MAILER_DSN`: Your email server configuration (if using email)

### 6. Generate APP_SECRET
```bash
# Generate a secure random string for APP_SECRET
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 7. Set Proper Permissions
```bash
# Set correct ownership (replace www-data with your web server user)
chown -R www-data:www-data .

# Set directory permissions
chmod -R 755 .
chmod -R 775 var/
chmod -R 775 public/uploads/

# Secure sensitive files
chmod 600 .env
```

### 8. Database Setup
```bash
# Create database schema
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# (Optional) Create admin user
php bin/console app:create-user
```

### 9. Clear and Warm Up Cache
```bash
# Clear production cache
php bin/console cache:clear --env=prod --no-debug

# Warm up cache
php bin/console cache:warmup --env=prod
```

### 10. Asset Management
```bash
# Install assets
php bin/console assets:install public --symlink --relative

# Install importmap assets
php bin/console importmap:install
```

### 11. Web Server Configuration

#### For Apache (.htaccess)
The project already includes `.htaccess` in the public directory. Make sure:
- `mod_rewrite` is enabled
- `AllowOverride All` is set in Apache config
- Document root points to `/path/to/chatapp/public`

#### Apache Virtual Host Example:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/chatapp/public

    <Directory /path/to/chatapp/public>
        AllowOverride All
        Require all granted
        Options -MultiViews
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>

    ErrorLog /var/log/apache2/chatapp_error.log
    CustomLog /var/log/apache2/chatapp_access.log combined
</VirtualHost>
```

#### For Nginx:
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
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/chatapp_error.log;
    access_log /var/log/nginx/chatapp_access.log;
}
```

### 12. SSL Certificate (Recommended)
```bash
# Install Certbot (for Let's Encrypt)
sudo apt install certbot python3-certbot-apache

# Generate SSL certificate
sudo certbot --apache -d your-domain.com
```

## 🔄 Updating Your Application

When you make changes and want to deploy updates:

```bash
# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations (if any)
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Install assets
php bin/console assets:install public
php bin/console importmap:install
```

## 📝 Important Notes

1. **Never commit `.env` file** - It contains sensitive data
2. **var/ and vendor/ directories** should not be in git
3. **Database backups** - Regular backups are essential
4. **File uploads** - The `public/uploads/` directory needs write permissions
5. **Security** - Use HTTPS in production (SSL certificate)

## 🐛 Troubleshooting

### Check logs:
```bash
tail -f var/log/prod.log
```

### Permission issues:
```bash
# Reset permissions
chmod -R 775 var/
chown -R www-data:www-data var/
```

### Clear cache if issues occur:
```bash
rm -rf var/cache/*
php bin/console cache:clear --env=prod
```

### Check PHP version and extensions:
```bash
php -v
php -m
```

## 📞 Need Help?
Check the Symfony documentation: https://symfony.com/doc/current/deployment.html

