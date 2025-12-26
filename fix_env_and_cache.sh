#!/bin/bash
# Fix .env.local and clear cache

echo "=== بررسی و اصلاح .env.local ==="

# Backup .env.local
if [ -f ".env.local" ]; then
    cp .env.local .env.local.backup
    echo "✓ Backup از .env.local ایجاد شد"
fi

# Check if APP_DEBUG exists
if grep -q "^APP_DEBUG=" .env.local 2>/dev/null; then
    # Update existing APP_DEBUG
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=1/' .env.local
    echo "✓ APP_DEBUG به 1 تنظیم شد"
else
    # Add APP_DEBUG if not exists
    echo "APP_DEBUG=1" >> .env.local
    echo "✓ APP_DEBUG=1 اضافه شد"
fi

# Check if APP_ENV exists
if grep -q "^APP_ENV=" .env.local 2>/dev/null; then
    # Update existing APP_ENV
    sed -i 's/^APP_ENV=.*/APP_ENV=dev/' .env.local
    echo "✓ APP_ENV به dev تنظیم شد"
else
    # Add APP_ENV if not exists
    echo "APP_ENV=dev" >> .env.local
    echo "✓ APP_ENV=dev اضافه شد"
fi

echo ""
echo "=== محتوای .env.local ==="
cat .env.local | grep -E "APP_ENV|APP_DEBUG"

echo ""
echo "=== پاک کردن کش ==="
rm -rf var/cache/*
echo "✓ کش پاک شد"

echo ""
echo "=== تست تنظیمات ==="
echo "لطفاً دستور زیر را اجرا کنید:"
echo "php bin/console debug:container --env-var=APP_ENV"
echo "php bin/console debug:container --env-var=APP_DEBUG"

