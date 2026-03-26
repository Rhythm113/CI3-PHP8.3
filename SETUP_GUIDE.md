# CodeIgniter 3 Setup Guide - Linux (Apache2 + PHP 8.3)

Complete setup guide for running this CI3 project on a fresh Linux server with Apache2, PHP 8.3, Composer, MySQL, PostgreSQL, and Redis.

---

## 1. System Update

```bash
sudo apt update && sudo apt upgrade -y
```

## 2. Install PHP 8.3

```bash
# Add PHP PPA (Ubuntu/Debian)
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 with required extensions
sudo apt install -y \
    php8.3 \
    php8.3-cli \
    php8.3-common \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-redis \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-gd \
    php8.3-intl \
    php8.3-bcmath \
    libapache2-mod-php8.3

# Verify
php -v
```

## 3. Install Apache2

```bash
sudo apt install -y apache2

# Enable required modules
sudo a2enmod rewrite
sudo a2enmod php8.3
sudo a2enmod headers

# Restart Apache
sudo systemctl restart apache2
sudo systemctl enable apache2

# Verify: visit http://your-server-ip/ - should show Apache default page
```

## 4. Install Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verify
composer -V
```

## 5. Install MySQL

```bash
sudo apt install -y mysql-server

# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE ci3_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'ci3_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ci3_db.* TO 'ci3_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 6. Install PostgreSQL

```bash
sudo apt install -y postgresql postgresql-contrib

# Switch to postgres user and create database
sudo -u postgres psql
```

```sql
CREATE DATABASE ci3_db;
CREATE USER ci3_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE ci3_db TO ci3_user;
\q
```

## 7. Install Redis

```bash
sudo apt install -y redis-server

# Enable and start Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Verify
redis-cli ping
# Should return: PONG
```

## 8. Deploy the Project

```bash
# Copy project files to your web root
sudo cp -r /path/to/CI3/* /var/www/ci3/
sudo chown -R www-data:www-data /var/www/ci3/
sudo chmod -R 755 /var/www/ci3/

# Set writable permissions for cache and logs
sudo chmod -R 775 /var/www/ci3/application/cache/
sudo chmod -R 775 /var/www/ci3/application/logs/

# Install Composer dependencies
cd /var/www/ci3
sudo -u www-data composer install --no-dev --optimize-autoloader
```

## 9. Configure Apache VirtualHost

Create a new virtual host config:

```bash
sudo nano /etc/apache2/sites-available/ci3.conf
```

Add:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/ci3

    <Directory /var/www/ci3>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Block access to sensitive directories
    <DirectoryMatch "^/var/www/ci3/(application|system|vendor)/">
        Require all denied
    </DirectoryMatch>

    ErrorLog ${APACHE_LOG_DIR}/ci3-error.log
    CustomLog ${APACHE_LOG_DIR}/ci3-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite ci3.conf
sudo a2dissite 000-default.conf   # Optional: disable default site
sudo systemctl reload apache2
```

## 10. Configure the Application

### Database (MySQL)

Edit `application/config/database.php`:

```php
$active_group = 'mysql';   // or 'postgre' for PostgreSQL

$db['mysql']['username'] = 'ci3_user';
$db['mysql']['password'] = 'your_password';
$db['mysql']['database'] = 'ci3_db';
```

### Database (PostgreSQL)

```php
$active_group = 'postgre';

$db['postgre']['username'] = 'ci3_user';
$db['postgre']['password'] = 'your_password';
$db['postgre']['database'] = 'ci3_db';
```

### JWT Secret

Edit `application/config/jwt.php`:

```php
$config['jwt_secret'] = 'your-random-secret-key-here';
```

Generate a random secret:

```bash
openssl rand -base64 64
```

### Redis

Edit `application/config/redis.php` - defaults work for local Redis with no password.

### Base URL

Edit `application/config/config.php`:

```php
$config['base_url'] = 'http://your-domain.com/';
```

## 11. PHP Configuration (Optional)

Edit php.ini for production:

```bash
sudo nano /etc/php/8.3/apache2/php.ini
```

Recommended settings:

```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
date.timezone = UTC
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

---

## Verification

| Test | Command / URL | Expected |
|------|--------------|----------|
| PHP version | `php -v` | PHP 8.3.x |
| Composer | `composer -V` | Composer version 2.x |
| Apache | `http://your-server-ip/` | CI3 welcome page |
| API Health | `GET /api/health` | JSON with `status: healthy` |
| Get Token | `POST /api/token` with `{"username":"test","password":"test"}` | JSON with `access_token` |
| Protected | `GET /api/protected` with `Authorization: Bearer <token>` | JSON with auth data |
| No Token | `GET /api/protected` (no header) | 401 Unauthorized |
| Rate Limit | Rapid requests to `/api/health` | 429 after 60 requests/min |
| Redis | `redis-cli ping` | `PONG` |

### Quick cURL Tests

```bash
# Health check
curl -s http://localhost/api/health | python3 -m json.tool

# Get JWT token
curl -s -X POST http://localhost/api/token \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}' | python3 -m json.tool

# Access protected endpoint (replace TOKEN)
curl -s http://localhost/api/protected \
  -H "Authorization: Bearer TOKEN" | python3 -m json.tool
```

---

## Project Structure

```
CI3/
├── application/
│   ├── config/
│   │   ├── config.php          # Base URL, index_page, composer autoload
│   │   ├── database.php        # MySQL + PostgreSQL configs
│   │   ├── autoload.php        # Auto-loaded libraries/helpers
│   │   ├── jwt.php             # JWT settings
│   │   ├── redis.php           # Redis connection settings
│   │   └── rate_limit.php      # Rate limiting settings
│   ├── controllers/
│   │   └── Api.php             # Example API controller
│   ├── core/
│   │   └── API_Controller.php  # Base API controller (JWT + rate limit)
│   └── libraries/
│       ├── Jwt_lib.php         # JWT wrapper (firebase/php-jwt)
│       ├── Redis_lib.php       # Redis wrapper (phpredis)
│       ├── Curl_lib.php        # cURL HTTP client
│       └── Rate_limiter.php    # IP-based rate limiting
├── system/                     # CI3 core (patched for PHP 8.3)
├── vendor/                     # Composer dependencies (after install)
├── .htaccess                   # Apache URL rewriting
├── composer.json               # firebase/php-jwt dependency
├── index.php                   # Front controller
└── SETUP_GUIDE.md              # This file
```
