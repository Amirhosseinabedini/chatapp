#!/bin/bash
# Diagnose Forbidden error - check document root configuration

echo "=== تشخیص مشکل Forbidden ==="

# Check current structure
echo ""
echo "=== ساختار فایل‌ها ==="
echo "مسیر فعلی: $(pwd)"
ls -la | grep -E "^d|index.php|\.htaccess" | head -10

# Check if document root might be pointing to public/
echo ""
echo "=== بررسی احتمال Document Root = public/ ==="
if [ -f "public/index.php" ]; then
    echo "✓ public/index.php وجود دارد"
    echo ""
    echo "⚠ احتمالاً Document Root به public/ اشاره می‌کند"
    echo "در این صورت باید:"
    echo "1. فایل .htaccess در public/ درست باشد"
    echo "2. یا Document Root را در پنل کنترل هاست تغییر دهید"
fi

# Check public/.htaccess
echo ""
echo "=== بررسی public/.htaccess ==="
if [ -f "public/.htaccess" ]; then
    echo "محتوای public/.htaccess:"
    cat public/.htaccess
else
    echo "✗ public/.htaccess وجود ندارد"
fi

# Create a simple test in public/
echo ""
echo "=== ایجاد فایل تست در public/ ==="
cat > public/test_simple.php << 'EOF'
<?php
echo "PHP is working!<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "<br>";
phpinfo();
?>
EOF
chmod 644 public/test_simple.php
echo "✓ public/test_simple.php ایجاد شد"

# Also create in root
cat > test_root.php << 'EOF'
<?php
echo "PHP is working in ROOT!<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "<br>";
phpinfo();
?>
EOF
chmod 644 test_root.php
echo "✓ test_root.php در root ایجاد شد"

echo ""
echo "=== دستورات تست ==="
echo ""
echo "1. تست Document Root = public/:"
echo "   https://chatapp.amirabedini.net/test_simple.php"
echo ""
echo "2. تست Document Root = root:"
echo "   https://chatapp.amirabedini.net/test_root.php"
echo ""
echo "3. تست index.php در root:"
echo "   https://chatapp.amirabedini.net/index.php"
echo ""
echo "4. تست index.php در public/:"
echo "   https://chatapp.amirabedini.net/public/index.php"
echo ""
echo "=== راه حل‌های ممکن ==="
echo ""
echo "اگر test_simple.php کار کرد اما test_root.php کار نکرد:"
echo "  → Document Root به public/ اشاره می‌کند"
echo "  → باید Document Root را در پنل کنترل هاست تغییر دهید"
echo ""
echo "اگر test_root.php کار کرد اما test_simple.php کار نکرد:"
echo "  → Document Root به root اشاره می‌کند (درست است)"
echo "  → مشکل از جای دیگری است"
echo ""
echo "اگر هیچکدام کار نکرد:"
echo "  → مشکل از تنظیمات Apache یا PHP است"
echo "  → باید با پشتیبانی هاست تماس بگیرید"

