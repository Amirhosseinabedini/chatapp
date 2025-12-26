#!/bin/bash
# اسکریپت استقرار برای سرور آنلاین
# استفاده: bash DEPLOY_ONLINE.sh

echo "=== شروع استقرار ==="

# بررسی مسیر فعلی
CURRENT_DIR=$(pwd)
echo "مسیر فعلی: $CURRENT_DIR"

# بررسی وجود composer
if command -v composer &> /dev/null; then
    COMPOSER_CMD="composer"
    echo "✓ Composer پیدا شد (global)"
elif [ -f "composer.phar" ]; then
    COMPOSER_CMD="php composer.phar"
    echo "✓ composer.phar پیدا شد"
else
    echo "⚠ Composer پیدا نشد. در حال دانلود..."
    curl -sS https://getcomposer.org/installer | php
    if [ -f "composer.phar" ]; then
        COMPOSER_CMD="php composer.phar"
        echo "✓ composer.phar دانلود شد"
    else
        echo "✗ خطا در دانلود composer"
        exit 1
    fi
fi

# دریافت تغییرات از Git
echo ""
echo "=== دریافت تغییرات از Git ==="
git pull origin master || {
    echo "✗ خطا در git pull"
    exit 1
}

# نصب وابستگی‌ها
echo ""
echo "=== نصب وابستگی‌ها ==="
$COMPOSER_CMD install --no-dev --optimize-autoloader || {
    echo "⚠ خطا در composer install (ممکن است نیاز به بررسی داشته باشد)"
}

# بررسی و تنظیم .env.local
echo ""
echo "=== بررسی تنظیمات محیط ==="
if [ ! -f ".env.local" ]; then
    echo "⚠ فایل .env.local وجود ندارد. ایجاد می‌شود..."
    cat > .env.local << 'EOF'
# تنظیمات محیط توسعه (Development Mode)
APP_ENV=dev
APP_DEBUG=1

# تنظیمات دیتابیس - SQLite (پیش‌فرض)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# کلید امنیتی
APP_SECRET=cf5cf7227a354ee254c17e6df08b80a8
EOF
    echo "✓ فایل .env.local ایجاد شد"
else
    echo "✓ فایل .env.local وجود دارد"
    # بررسی اینکه APP_DEBUG=1 است
    if ! grep -q "APP_DEBUG=1" .env.local; then
        echo "⚠ APP_DEBUG=1 در .env.local نیست. لطفاً بررسی کنید."
    fi
fi

# پاک کردن کش
echo ""
echo "=== پاک کردن کش ==="
php bin/console cache:clear --env=dev || {
    echo "⚠ خطا در cache:clear (ممکن است نیاز به بررسی داشته باشد)"
}

# اجرای migration
echo ""
echo "=== اجرای Migration ==="
php bin/console doctrine:migrations:migrate --no-interaction || {
    echo "⚠ خطا در migration (ممکن است همه migrationها قبلاً اجرا شده باشند)"
}

# تنظیم مجوزها
echo ""
echo "=== تنظیم مجوزها ==="
chmod -R 775 var/ 2>/dev/null || echo "⚠ خطا در تنظیم مجوز var/"
chmod -R 775 public/uploads/ 2>/dev/null || echo "⚠ خطا در تنظیم مجوز public/uploads/"

echo ""
echo "=== استقرار کامل شد ==="
echo "لطفاً سایت را در مرورگر تست کنید: https://chatapp.amirabedini.net/login"

