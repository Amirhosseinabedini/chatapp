#!/bin/bash
# Fix Forbidden error - check document root and create necessary files

echo "=== بررسی و رفع مشکل Forbidden ==="

# Check document root location
echo ""
echo "=== بررسی مسیر Document Root ==="
DOCUMENT_ROOT="${DOCUMENT_ROOT:-$(pwd)}"
echo "Document Root: $DOCUMENT_ROOT"
echo "Current Directory: $(pwd)"

# Check if we're in the right place
if [ ! -f "index.php" ]; then
    echo "✗ index.php در مسیر فعلی پیدا نشد"
    exit 1
fi

# Create .htaccess in public/ if it doesn't exist
echo ""
echo "=== بررسی .htaccess در public/ ==="
if [ ! -f "public/.htaccess" ]; then
    echo "⚠ public/.htaccess وجود ندارد. ایجاد می‌شود..."
    cat > public/.htaccess << 'EOF'
# Symfony public directory .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
EOF
    chmod 644 public/.htaccess
    echo "✓ public/.htaccess ایجاد شد"
else
    echo "✓ public/.htaccess وجود دارد"
fi

# Ensure root .htaccess is correct
echo ""
echo "=== بررسی .htaccess در root ==="
if [ -f ".htaccess" ]; then
    # Check if it has the right content
    if ! grep -q "RewriteEngine On" .htaccess; then
        echo "⚠ .htaccess محتوای درستی ندارد"
    else
        echo "✓ .htaccess درست است"
    fi
else
    echo "✗ .htaccess وجود ندارد"
fi

# Check index.php permissions
echo ""
echo "=== بررسی مجوزهای فایل‌های اصلی ==="
chmod 644 index.php
chmod 644 public/index.php
chmod 644 .htaccess
echo "✓ مجوزهای فایل‌های اصلی تنظیم شد"

# Check if var/cache exists and is writable
echo ""
echo "=== بررسی دایرکتوری‌های قابل نوشتن ==="
mkdir -p var/cache var/log
chmod -R 775 var/
mkdir -p public/uploads
chmod -R 775 public/uploads/
echo "✓ دایرکتوری‌های قابل نوشتن بررسی شدند"

# Create a test file to check if web server can access
echo ""
echo "=== ایجاد فایل تست ==="
echo "<?php phpinfo(); ?>" > test.php
chmod 644 test.php
echo "✓ فایل test.php ایجاد شد"
echo ""
echo "=== دستورات تست ==="
echo "1. باز کنید: https://chatapp.amirabedini.net/test.php"
echo "   اگر phpinfo نمایش داده شد، مشکل از مسیر document root نیست"
echo ""
echo "2. باز کنید: https://chatapp.amirabedini.net/"
echo "   باید به index.php هدایت شود"
echo ""
echo "3. بعد از تست، فایل test.php را حذف کنید:"
echo "   rm test.php"

