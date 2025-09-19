# Chat App - Advanced Version 🚀

یک اپلیکیشن چت پیشرفته و کامل با قابلیت‌های مدرن و حرفه‌ای.

## ✨ فیچرهای پیاده‌سازی شده

### 🏢 Group Chat (چت گروهی)
- **نقش‌ها**: Owner, Moderator, Member
- **مدیریت اعضا**: دعوت، حذف، تغییر نقش
- **گروه‌های عمومی و خصوصی**
- **سیستم دعوت با کد و انقضا**

### 💬 Advanced Messaging
- **Reply/Quote**: پاسخ به پیام‌های خاص
- **Edit/Delete**: ویرایش و حذف با audit trail
- **Pin Messages**: سنجاق کردن پیام‌های مهم
- **System Messages**: پیام‌های سیستم (ورود/خروج، تغییر نقش)

### 😀 Emojis & Reactions
- **Emoji Picker**: انتخابگر emoji با دسته‌بندی
- **Reactions**: واکنش به پیام‌ها
- **GIF Support**: پشتیبانی از GIF (آماده برای Tenor API)
- **Sticker Support**: پشتیبانی از استیکر

### 🔒 User Management
- **Block Users**: مسدود کردن کاربران
- **Mute Conversations**: بی‌صدا کردن مکالمات
- **Presence System**: وضعیت آنلاین/آفلاین
- **Typing Indicators**: نشانگر در حال تایپ

### 📱 PWA (Progressive Web App)
- **Installable**: قابل نصب روی موبایل و دسکتاپ
- **Offline Support**: کار در حالت آفلاین
- **Push Notifications**: اعلان‌های push
- **Background Sync**: همگام‌سازی در پس‌زمینه

### 🛡️ Admin Panel
- **User Management**: مدیریت کاربران (بن/آنبن)
- **Group Management**: مدیریت گروه‌ها
- **Statistics Dashboard**: داشبورد آمار
- **Logs Viewer**: مشاهده لاگ‌ها
- **Content Moderation**: نظارت بر محتوا

## 🛠️ تکنولوژی‌های استفاده شده

### Backend
- **Symfony 7**: Framework اصلی
- **Doctrine ORM**: مدیریت دیتابیس
- **PostgreSQL**: دیتابیس اصلی
- **Mercure**: Real-time communication
- **JWT**: احراز هویت

### Frontend
- **Bootstrap 5**: UI Framework
- **Font Awesome**: آیکون‌ها
- **JavaScript ES6+**: منطق frontend
- **Service Worker**: PWA functionality
- **IndexedDB**: ذخیره‌سازی آفلاین

### Real-time Features
- **Mercure Hub**: WebSocket alternative
- **EventSource**: Server-Sent Events
- **Push API**: Browser notifications

## 📊 Database Schema

### Tables
- `user`: کاربران
- `group`: گروه‌ها
- `group_member`: اعضای گروه
- `group_message`: پیام‌های گروهی
- `message`: پیام‌های مستقیم
- `user_block`: مسدودسازی کاربران

### Key Features
- **Audit Trail**: تاریخچه تغییرات
- **Soft Delete**: حذف نرم
- **JSON Fields**: ذخیره reactions و metadata
- **Indexes**: بهینه‌سازی جستجو

## 🚀 راه‌اندازی

### پیش‌نیازها
```bash
# PHP 8.2+
# PostgreSQL 13+
# Composer
# Node.js (برای assets)
```

### نصب
```bash
# Clone repository
git clone <repository-url>
cd chatapp

# Install dependencies
composer install

# Setup database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Create admin user
php bin/console app:create-user --email=admin@chatapp.com --password=admin123 --role=ROLE_ADMIN

# Start server
symfony serve -d
```

### تنظیمات Mercure
```bash
# Install Mercure
docker run -p 8080:80 dunglas/mercure

# یا استفاده از Mercure Hub
```

## 👥 کاربران پیش‌فرض

| Email | Password | Role |
|-------|----------|------|
| admin@chatapp.com | admin123 | ROLE_USER |
| superadmin@chatapp.com | superadmin123 | ROLE_ADMIN |
| test@chatapp.com | test123 | ROLE_USER |
| john@chatapp.com | john123 | ROLE_USER |

## 🎯 استفاده

### 1. ورود
- برو به `http://127.0.0.1:8000/login`
- با یکی از کاربران بالا وارد شو

### 2. Direct Chat
- برو به `/chat/`
- کاربران را انتخاب کن و چت کن

### 3. Group Chat
- برو به `/groups/`
- گروه جدید بساز یا به گروه موجود بپیوند

### 4. Admin Panel
- با کاربر admin وارد شو
- برو به `/admin/` برای مدیریت

## 🔧 API Endpoints

### Groups
- `GET /groups/` - لیست گروه‌ها
- `POST /groups/create` - ایجاد گروه
- `GET /groups/{id}` - نمایش گروه
- `POST /groups/join/{code}` - پیوستن با کد دعوت

### Messages
- `POST /group-messages/send` - ارسال پیام
- `POST /group-messages/{id}/reaction` - واکنش
- `POST /group-messages/{id}/pin` - سنجاق کردن

### Emojis
- `GET /emoji/popular` - emoji های محبوب
- `GET /emoji/search?q=query` - جستجوی emoji
- `GET /emoji/gif/trending` - GIF های ترند

## 📱 PWA Features

### نصب
- روی موبایل: "Add to Home Screen"
- روی دسکتاپ: دکمه "Install App"

### آفلاین
- پیام‌ها در IndexedDB ذخیره می‌شوند
- هنگام آنلاین شدن، ارسال می‌شوند

### اعلان‌ها
- اعلان‌های push برای پیام‌های جدید
- Badge در آیکون اپ

## 🛡️ امنیت

### Authentication
- JWT tokens
- CSRF protection
- Session management

### Authorization
- Role-based access control
- Group permissions
- Message-level permissions

### Data Protection
- Input validation
- SQL injection prevention
- XSS protection

## 📈 Performance

### Caching
- Service Worker caching
- Static asset caching
- Database query optimization

### Real-time
- Mercure for real-time updates
- Efficient message delivery
- Connection pooling

## 🔮 فیچرهای آینده

### Encryption
- [ ] End-to-end encryption
- [ ] Database encryption at rest
- [ ] Message encryption

### Advanced Features
- [ ] Voice messages
- [ ] Video calls
- [ ] Screen sharing
- [ ] File sharing improvements

### Analytics
- [ ] Message analytics
- [ ] User behavior tracking
- [ ] Performance metrics

## 🐛 Debugging

### Logs
```bash
# View logs
tail -f var/log/dev.log

# Clear cache
php bin/console cache:clear
```

### Database
```bash
# Check migrations
php bin/console doctrine:migrations:status

# Reset database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 📞 پشتیبانی

برای گزارش باگ یا درخواست فیچر جدید:
1. Issue در GitHub ایجاد کن
2. جزئیات کامل مشکل را شرح بده
3. Steps to reproduce ارائه بده

## 📄 License

این پروژه تحت مجوز MIT منتشر شده است.

---

**🎉 مبارک! اپلیکیشن چت پیشرفته شما آماده است!**

برای شروع، سرور را راه‌اندازی کن و با کاربران مختلف تست کن.

