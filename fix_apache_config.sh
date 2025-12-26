#!/bin/bash
# Fix Apache configuration issues for shared hosting

echo "=== رفع مشکل Forbidden در سطح Apache ==="

# Check current user and permissions
echo ""
echo "=== اطلاعات کاربر و مجوزها ==="
echo "User: $(whoami)"
echo "Group: $(id -gn)"
echo "Home: $HOME"

# Fix ownership if possible (usually not possible in shared hosting)
echo ""
echo "=== تنظیم مجوزهای فایل‌ها ==="

# Set all files to 644
find . -type f ! -name "*.sh" -exec chmod 644 {} \; 2>/dev/null
echo "✓ مجوزهای فایل‌ها به 644 تنظیم شد"

# Set all directories to 755
find . -type d -exec chmod 755 {} \; 2>/dev/null
echo "✓ مجوزهای دایرکتوری‌ها به 755 تنظیم شد"

# Special permissions for scripts
chmod +x *.sh 2>/dev/null
chmod +x bin/console 2>/dev/null
echo "✓ مجوزهای فایل‌های اجرایی تنظیم شد"

# Writable directories
chmod -R 775 var/ 2>/dev/null
chmod -R 775 public/uploads/ 2>/dev/null
echo "✓ مجوزهای دایرکتوری‌های قابل نوشتن تنظیم شد"

# Check .htaccess files
echo ""
echo "=== بررسی فایل‌های .htaccess ==="

# Root .htaccess
if [ -f ".htaccess" ]; then
    echo "✓ .htaccess در root وجود دارد"
    # Ensure it's readable
    chmod 644 .htaccess
else
    echo "✗ .htaccess در root وجود ندارد"
fi

# Public .htaccess
if [ -f "public/.htaccess" ]; then
    echo "✓ public/.htaccess وجود دارد"
    chmod 644 public/.htaccess
else
    echo "⚠ public/.htaccess وجود ندارد - ایجاد می‌شود"
    cat > public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
EOF
    chmod 644 public/.htaccess
fi

# Create a minimal .htaccess if root one is problematic
echo ""
echo "=== ایجاد .htaccess ساده برای تست ==="
cat > .htaccess.simple << 'EOF'
# Simple .htaccess for testing
DirectoryIndex index.php index.html

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Allow access to existing files
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]
    
    # Route everything else to index.php
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
EOF
chmod 644 .htaccess.simple
echo "✓ .htaccess.simple ایجاد شد"

# Create a very simple test file
echo ""
echo "=== ایجاد فایل تست بسیار ساده ==="
cat > info.txt << 'EOF'
This is a simple text file to test if the web server can access files.
If you can see this, the web server is working but PHP might have issues.
EOF
chmod 644 info.txt
echo "✓ info.txt ایجاد شد"

echo ""
echo "=== دستورات تست ==="
echo ""
echo "1. تست فایل متنی (باید کار کند):"
echo "   https://chatapp.amirabedini.net/info.txt"
echo ""
echo "2. اگر info.txt کار کرد، مشکل از PHP یا .htaccess است"
echo ""
echo "3. اگر info.txt هم کار نکرد، مشکل از Document Root است"
echo ""
echo "=== راه حل‌های ممکن ==="
echo ""
echo "اگر همه چیز Forbidden است:"
echo ""
echo "1. بررسی Document Root در پنل کنترل هاست:"
echo "   - Document Root باید به: /homepages/13/d4299167939/htdocs/chatapp"
echo "   - یا به: /homepages/13/d4299167939/htdocs/chatapp/public"
echo "   اشاره کند"
echo ""
echo "2. بررسی AllowOverride در Apache:"
echo "   - باید AllowOverride All باشد"
echo "   - این معمولاً در پنل کنترل هاست تنظیم می‌شود"
echo ""
echo "3. بررسی mod_rewrite:"
echo "   - باید فعال باشد"
echo "   - در پنل کنترل هاست بررسی کنید"
echo ""
echo "4. تماس با پشتیبانی هاست:"
echo "   - اگر Document Root درست است اما هنوز Forbidden است"
echo "   - ممکن است نیاز به تنظیمات خاص در سطح سرور باشد"
echo ""
echo "=== فایل‌های ایجاد شده ==="
echo "- .htaccess.simple (برای تست)"
echo "- info.txt (برای تست دسترسی)"
echo ""
echo "بعد از تست، می‌توانید این فایل‌ها را حذف کنید"

