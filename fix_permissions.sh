#!/bin/bash
# Fix file permissions for web server

echo "=== تنظیم مجوزهای فایل‌ها ==="

# Set proper permissions for directories
find . -type d -exec chmod 755 {} \;
echo "✓ مجوزهای دایرکتوری‌ها تنظیم شد (755)"

# Set proper permissions for files
find . -type f -exec chmod 644 {} \;
echo "✓ مجوزهای فایل‌ها تنظیم شد (644)"

# Special permissions for executable files
chmod +x bin/console 2>/dev/null
chmod +x *.sh 2>/dev/null
echo "✓ مجوزهای فایل‌های اجرایی تنظیم شد"

# Writable directories need 775
chmod -R 775 var/ 2>/dev/null
chmod -R 775 public/uploads/ 2>/dev/null
echo "✓ مجوزهای دایرکتوری‌های قابل نوشتن تنظیم شد (775)"

# Check .htaccess
if [ -f ".htaccess" ]; then
    chmod 644 .htaccess
    echo "✓ مجوز .htaccess تنظیم شد"
fi

# Check index.php
if [ -f "index.php" ]; then
    chmod 644 index.php
    echo "✓ مجوز index.php تنظیم شد"
fi

# Check public/index.php
if [ -f "public/index.php" ]; then
    chmod 644 public/index.php
    echo "✓ مجوز public/index.php تنظیم شد"
fi

echo ""
echo "=== بررسی مجوزهای مهم ==="
ls -la index.php 2>/dev/null | head -1
ls -la public/index.php 2>/dev/null | head -1
ls -ld var/ 2>/dev/null | head -1
ls -ld public/uploads/ 2>/dev/null | head -1

echo ""
echo "✓ مجوزها تنظیم شدند. لطفاً سایت را دوباره تست کنید."

