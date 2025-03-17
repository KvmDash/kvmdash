# Apache Configuration for KVM Dashboard

This guide describes the Apache configuration for the KVM Dashboard.
## Prerequisites

- Apache 2.4 or higher
- PHP 8.2 or higher with FPM
- Following Apache modules:
  - headers
  - rewrite 
  - alias

## Installation of Required Modules

```bash
# Activate Apache modules
sudo a2enmod headers
sudo a2enmod rewrite
sudo a2enmod alias

# Restart Apache
sudo systemctl restart apache2
```

## Virtual Host Configuration

The Virtual Host configuration must be created under `/etc/apache2/sites-available/001-kvmdash.conf`:

```apache
<VirtualHost *:80>
    ServerName kvmdash
    DocumentRoot /var/www/kvmdash/frontend/dist

    # Debug Logging
    ErrorLog ${APACHE_LOG_DIR}/kvmdash_error.log
    CustomLog ${APACHE_LOG_DIR}/kvmdash_access.log combined

    # Frontend FIRST, but with exception for /api and /bundles
    <Directory /var/www/kvmdash/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        
        RewriteEngine On
        # If request starts with /api or /bundles, don't redirect
        RewriteCond %{REQUEST_URI} !^/(api|bundles)
        
        # If file doesn't exist, redirect to index.html
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.html [L]
    </Directory>

    # Backend API 
    AliasMatch "^/api" "/var/www/kvmdash/backend/public/index.php"
    
    # API Platform Assets
    Alias "/bundles" "/var/www/kvmdash/backend/public/bundles"
    
    # Backend Directories
    <Directory /var/www/kvmdash/backend/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted

        SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Methods "GET,POST,PUT,DELETE,OPTIONS"
        Header always set Access-Control-Allow-Headers "Authorization,Content-Type"
    </Directory>

    <Directory /var/www/kvmdash/backend/public/bundles>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

## Activating the Configuration

```bash
# Create symlink
sudo a2ensite 001-kvmdash.conf

# Optional: Deactivate default site
sudo a2dissite 000-default.conf

# Reload Apache
sudo systemctl reload apache2
```

## Verifying the Configuration

```bash
# Test Apache configuration
sudo apache2ctl configtest

# Display active modules
apache2ctl -M

# Monitor log files
tail -f /var/log/apache2/kvmdash_error.log
tail -f /var/log/apache2/kvmdash_access.log
```

## Structure

- Frontend (vite): `/var/www/kvmdash/frontend/dist`
- Backend (Symfony): `/var/www/kvmdash/www/html/backend/public`
- API Endpoint: `http://kvmdash/api`

## Troubleshooting

If errors occur, check:
1. Whether all required modules are activated
2. The permissions of the directories
3. The Apache error logs
4. Whether the symlinks are set correctly

## Security Notes

- The CORS headers are currently configured for all origins (`*`)
- In production environments, the CORS headers should be restricted
- SSL/TLS should be configured for production environments
