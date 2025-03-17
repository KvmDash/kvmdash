# Project Description: KVMDash

<table style="border-collapse: collapse; width: 100%;">
    <tr>
        <td style="width: 150px; padding: 10px; vertical-align: middle;">
            <img src="frontend/src/assets/kvmdash.svg" alt="KvmDash Logo" style="max-width: 100%;">
        </td>
        <td style="padding: 10px; vertical-align: middle;">
            KVMDash is a web application that enables the management of Virtual Machines (VMs) on Linux systems.
            With a user-friendly interface, KVMDash facilitates the administration and monitoring of virtualization environments.
        </td>
    </tr>
</table>

## 📑 Table of Contents
- [Features](#features)
- [Demo Videos](#demo-videos)
- [System Requirements](#system-requirements)
- [Installation](#installation)
  - [1. Install System Requirements](#1-install-system-requirements)
  - [2. Install KVMDash](#2-install-kvmdash)
  - [3. Set Up Backend](#3-set-up-backend)
  - [4. Set Up Frontend](#4-set-up-frontend)
  - [5. Set Up Web Server](#5-set-up-web-server)
  - [6. Direct Testing Without Apache (Development Environment)](#6-direct-testing-without-apache-development-environment)
- [Documentation](#documentation)

## Features

### VM Management
* Create, delete, and configure VMs and containers through the web interface.
* Use templates for quick and standardized creation of VMs.

### System Monitoring
* Real-time monitoring of resources such as CPU, memory, disk usage, and other important system metrics.
* Clear visualization of system performance for optimal control and error analysis.

## Demo Videos

https://github.com/user-attachments/assets/ec76e8fa-f9b1-487d-87a8-6d370dbfb73c

## System Requirements

* Node.js 18.x or newer
* npm 9.x or newer
* Composer 2.x
* KVM and libvirt
* Apache Web Server with PHP 8.2

## Installation

### 1. Install System Requirements

#### KVM/QEMU and Libvirt
For complete instructions on installing KVM on Debian, see [KVM Installation Guide](docs/kvm-Debian.md).

```bash
# Short version: Install KVM and Libvirt
apt update
apt install qemu-kvm qemu-utils libvirt-daemon-system virtinst bridge-utils
```

Detailed guide for Libvirt configuration: [Libvirt Configuration](docs/libvirt-Debian.md)

#### Node.js and npm
```bash
# Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### PHP and Extensions
```bash
# PHP and Extensions
sudo apt install php8.2 php8.2-xml php8.2-curl php8.2-mysql php8.2-mbstring php8.2-zip
```

#### Composer
```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Database
KVMDash uses Doctrine ORM and supports various database systems:

```bash
# MySQL
sudo apt install mysql-server

# Or PostgreSQL
# sudo apt install postgresql

# SQLite is supported without additional installation
```

#### Web Server
Apache configuration for KVMDash: [Apache Setup Guide](docs/apache-Debian.md)
```bash
# Apache and required modules
sudo apt install apache2
sudo a2enmod headers rewrite alias

# Add web server user to required groups
sudo usermod -aG libvirt-qemu,libvirt,kvm www-data

# Restart web server to apply group changes
sudo systemctl restart apache2
```

### 2. Install KVMDash

```bash
# Clone repository
git clone https://github.com/KvmDash/kvmdash.git /var/www/kvmdash
cd /var/www/kvmdash

# Initialize and update submodules (for Spice Client)
git submodule update --init --recursive
```

### 3. Set Up Backend

```bash
# In the backend directory
cd /var/www/kvmdash

# Create .env.local
cp .env .env.local

# Adjust .env.local (important settings):
# APP_ENV=dev
# APP_SECRET=YourSecretKey
# Database connection (choose one of the following options):
# MySQL: DATABASE_URL="mysql://user:password@127.0.0.1:3306/kvmdash"
# PostgreSQL: DATABASE_URL="postgresql://user:password@127.0.0.1:5432/kvmdash?serverVersion=15&charset=utf8"
# SQLite: DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
# JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
# JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
# JWT_PASSPHRASE=YourJWTPassphrase

# Generate JWT keys
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Set up database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Create first admin user
php bin/console app:create-user --admin "admin@example.com" "YourPassword"
```

### 4. Set Up Frontend

```bash
# Configure SPICE HTML5 Client
cd /var/www/kvmdash/frontend/src/assets/spice-html5
cp package.json.in package.json
sed -i 's/VERSION/0.3/g' package.json

# Switch to frontend directory
cd /var/www/kvmdash/frontend

# Install dependencies
npm install

```
Adjust Vite configuration
Open the file src/config.ts and adjust the backend settings

Example for `src/config.ts`:
```javascript
/// Test environment
// export const BACKEND_PORT = 8000; // Backend port
// export const BACKEND_HOST = 'localhost'; // Backend hostname/IP address

export const BACKEND_PORT = 80; // Backend port
export const BACKEND_HOST = 'kvmdash'; // Backend hostname/IP address
```

### 5. Set Up Web Server

Copy the Apache configuration from the docs directory:

```bash
cp /var/www/kvmdash/docs/001-kvmdash.conf /etc/apache2/sites-available/
sudo a2ensite 001-kvmdash.conf
sudo systemctl reload apache2
```

Complete guide for Apache configuration: [Apache Setup](docs/apache-Debian.md)

### 6. Direct Testing Without Apache (Development Environment)

For quick testing or development, KVMDash can also be run without Apache:

#### Backend with Symfony Development Server

```bash
# In the backend directory
cd /var/www/kvmdash/backend

# Start Symfony Development Server (allow all IPs)
symfony server:start --allow-all-ip
```

The backend server is now accessible at `http://your-ip:8000`.

#### Frontend with Vite Development Server

```bash
# In a new terminal, in the frontend directory
cd /var/www/kvmdash/frontend

# Start Vite Dev Server
npm run dev
```

The frontend server is available by default at `http://localhost:5173`.

> **Note:** This method is intended for development and testing only. The Apache configuration is recommended for production environments.

## Documentation

The API documentation is available after starting the server at:
```
https://your-server/api/docs
```

Additional documentation:
- [KVM Installation and Configuration](docs/kvm-Debian.md)
- [Libvirt Setup and Management](docs/libvirt-Debian.md)
- [Apache Web Server Configuration](docs/apache-Debian.md)

## TurnKey Linux Compatibility

KVMDash works seamlessly with [TurnKey Linux](http://mirror.turnkeylinux.org/turnkeylinux/images/iso/) images. With TurnKey Linux, you get access to over 100 pre-built, ready-to-use server appliances based on Debian.

### Integration Benefits:
- **Rapid Deployment**: Immediately ready-to-use specialized servers without complex configuration
- **Wide Range**: From web server stacks (LAMP, LEMP) to CMS (WordPress, Drupal) and collaboration tools
- **Security**: Regularly updated, hardened images with automatic security updates

### Using TurnKey Images in KVMDash:
1. Download the desired TurnKey image from the official website
2. Import the image into KVMDash as a VM template
3. Create new VMs based on these templates with just a few clicks

TurnKey images provide an ideal complement to KVMDash for quickly deploying productive server environments without having to perform complex manual configurations.





