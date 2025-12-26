# دستورات استقرار برای سرور آنلاین

## مراحل استقرار

### 1. اتصال به سرور و رفتن به دایرکتوری پروژه
```bash
ssh your-server
cd /path/to/chatapp
```

### 2. دریافت آخرین تغییرات از Git
```bash
git pull origin master
```

### 3. تنظیم محیط توسعه (Dev Mode)
```bash
# بررسی فایل .env.local
cat .env.local

# اگر وجود ندارد یا نیاز به به‌روزرسانی دارد:
cat > .env.local << 'EOF'
# تنظیمات محیط توسعه (Development Mode)
APP_ENV=dev
APP_DEBUG=1

# تنظیمات دیتابیس - SQLite (پیش‌فرض)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# کلید امنیتی
APP_SECRET=cf5cf7227a354ee254c17e6df08b80a8

# Mercure Configuration (اگر متفاوت از .env است)
# MERCURE_URL=http://127.0.0.1:3000/.well-known/mercure
# MERCURE_PUBLIC_URL=http://127.0.0.1:3000/.well-known/mercure
# MERCURE_PUBLISHER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!'
# MERCURE_SUBSCRIBER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!'
EOF
```

### 4. نصب وابستگی‌ها (در صورت نیاز)
```bash
composer install --no-dev --optimize-autoloader
```

### 5. پاک کردن کش و به‌روزرسانی
```bash
# پاک کردن کش Symfony
php bin/console cache:clear --env=dev

# یا اگر نیاز به پاک کردن کامل دارید:
rm -rf var/cache/*
php bin/console cache:warmup --env=dev
```

### 6. اجرای Migration های دیتابیس (در صورت نیاز)
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### 7. تنظیم مجوزهای فایل‌ها
```bash
# تنظیم مجوزهای مناسب برای دایرکتوری‌های قابل نوشتن
chmod -R 775 var/
chmod -R 775 public/uploads/
```

### 8. تست دسترسی
```bash
# بررسی اینکه آیا فایل index.php درست کار می‌کند
php -S localhost:8000 -t public/
# یا
php index.php
```

### 9. تست از طریق مرورگر
```bash
# باز کردن در مرورگر:
# https://chatapp.amirabedini.net/login
```

## دستورات تست سریع

### بررسی تنظیمات محیط
```bash
# بررسی APP_ENV و APP_DEBUG
php bin/console debug:container --env-var=APP_ENV
php bin/console debug:container --env-var=APP_DEBUG
```

### بررسی خطاها
```bash
# مشاهده لاگ‌های خطا
tail -f var/log/dev.log

# یا اگر لاگ در جای دیگری است:
tail -f /path/to/your/log/file.log
```

### تست اتصال دیتابیس
```bash
php bin/console doctrine:schema:validate
```

## نکات مهم

1. **تأیید تنظیمات**: مطمئن شوید که `.env.local` دارای `APP_ENV=dev` و `APP_DEBUG=1` است
2. **Mercure URL**: اگر Mercure در سرور آنلاین در آدرس دیگری است، `MERCURE_PUBLIC_URL` را در `.env.local` تنظیم کنید
3. **امنیت**: در محیط توسعه، `APP_DEBUG=1` فعال است که اطلاعات خطا را نمایش می‌دهد. برای تولید، باید `APP_DEBUG=0` و `APP_ENV=prod` تنظیم شود
4. **کش**: بعد از هر تغییر در فایل‌های `.env`، کش را پاک کنید

## دستورات یکجا (Quick Deploy)

### اگر در دایرکتوری پروژه هستید (مثل ~/chatapp):
```bash
# بررسی مسیر فعلی
pwd

# دریافت تغییرات (اگر قبلاً pull نکرده‌اید)
git pull origin master

# بررسی وجود composer
which composer
# یا
ls -la composer.phar

# اگر composer.phar وجود دارد:
php composer.phar install --no-dev --optimize-autoloader

# اگر composer به صورت global نصب است:
composer install --no-dev --optimize-autoloader

# پاک کردن کش
php bin/console cache:clear --env=dev

# اجرای migration (در صورت نیاز)
php bin/console doctrine:migrations:migrate --no-interaction

# تنظیم مجوزها
chmod -R 775 var/ public/uploads/
```

### اگر composer پیدا نشد:
```bash
# دانلود composer.phar
curl -sS https://getcomposer.org/installer | php

# سپس استفاده کنید:
php composer.phar install --no-dev --optimize-autoloader
```

