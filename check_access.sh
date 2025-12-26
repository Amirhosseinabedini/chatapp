#!/bin/bash
# Check file access and paths

echo "=== بررسی مسیرها و دسترسی‌ها ==="

# Check current directory
echo "مسیر فعلی: $(pwd)"

# Check if index.php exists
echo ""
echo "=== بررسی فایل‌های اصلی ==="
[ -f "index.php" ] && echo "✓ index.php وجود دارد" || echo "✗ index.php پیدا نشد"
[ -f "public/index.php" ] && echo "✓ public/index.php وجود دارد" || echo "✗ public/index.php پیدا نشد"
[ -f ".htaccess" ] && echo "✓ .htaccess وجود دارد" || echo "✗ .htaccess پیدا نشد"

# Check .htaccess content
echo ""
echo "=== بررسی محتوای .htaccess ==="
if [ -f ".htaccess" ]; then
    head -5 .htaccess
else
    echo "⚠ .htaccess وجود ندارد"
fi

# Check if var directory is writable
echo ""
echo "=== بررسی دسترسی نوشتن ==="
[ -w "var/" ] && echo "✓ var/ قابل نوشتن است" || echo "✗ var/ قابل نوشتن نیست"
[ -w "public/uploads/" ] && echo "✓ public/uploads/ قابل نوشتن است" || echo "✗ public/uploads/ قابل نوشتن نیست"

# Check file permissions
echo ""
echo "=== بررسی مجوزهای فایل‌های مهم ==="
ls -la index.php 2>/dev/null
ls -la public/index.php 2>/dev/null
ls -la .htaccess 2>/dev/null

# Check if there's a .htaccess in public/
echo ""
echo "=== بررسی .htaccess در public/ ==="
[ -f "public/.htaccess" ] && echo "✓ public/.htaccess وجود دارد" || echo "⚠ public/.htaccess وجود ندارد"

# Check document root (if available)
echo ""
echo "=== اطلاعات محیط ==="
echo "USER: $USER"
echo "HOME: $HOME"

# Test PHP syntax
echo ""
echo "=== تست syntax PHP ==="
php -l index.php 2>&1 | head -1
php -l public/index.php 2>&1 | head -1

echo ""
echo "=== نکات ==="
echo "اگر هنوز Forbidden می‌دهد:"
echo "1. بررسی کنید که document root به root پروژه اشاره می‌کند (نه public/)"
echo "2. بررسی کنید که mod_rewrite در Apache فعال است"
echo "3. بررسی کنید که AllowOverride در Apache تنظیم شده است"

