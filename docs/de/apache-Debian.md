# Apache Konfiguration für KVM Dashboard

Diese Anleitung beschreibt die Apache-Konfiguration für das KVM Dashboard.
## Voraussetzungen

- Apache 2.4 oder höher
- PHP 8.2 oder höher mit FPM
- Folgende Apache-Module:
  - headers
  - rewrite 
  - alias

## Installation der benötigten Module

```bash
# Apache Module aktivieren
sudo a2enmod headers
sudo a2enmod rewrite
sudo a2enmod alias

# Apache neu starten
sudo systemctl restart apache2
```

## Virtual Host Konfiguration

Die Virtual Host Konfiguration muss unter `/etc/apache2/sites-available/001-kvmdash.conf` erstellt werden:

```apache
<VirtualHost *:80>
    ServerName kvmdash
    DocumentRoot /var/www/kvmdash/frontend/dist

    # Debug Logging
    ErrorLog ${APACHE_LOG_DIR}/kvmdash_error.log
    CustomLog ${APACHE_LOG_DIR}/kvmdash_access.log combined

    # Frontend ZUERST, aber mit Ausnahme für /api und /bundles
    <Directory /var/www/kvmdash/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        
        RewriteEngine On
        # Wenn Anfrage mit /api oder /bundles beginnt, nicht weiterleiten
        RewriteCond %{REQUEST_URI} !^/(api|bundles)
        
        # Wenn Datei nicht existiert, zu index.html weiterleiten
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.html [L]
    </Directory>

    # Backend API 
    AliasMatch "^/api" "/var/www/kvmdash/backend/public/index.php"
    
    # API Platform Assets
    Alias "/bundles" "/var/www/kvmdash/backend/public/bundles"
    
    # Backend Verzeichnisse
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

## Aktivierung der Konfiguration

```bash
# Symlink erstellen
sudo a2ensite 001-kvmdash.conf

# Optional: Default Site deaktivieren
sudo a2dissite 000-default.conf

# Apache neu laden
sudo systemctl reload apache2
```

## Überprüfung der Konfiguration

```bash
# Apache Konfiguration testen
sudo apache2ctl configtest

# Aktive Module anzeigen
apache2ctl -M

# Log-Dateien überwachen
tail -f /var/log/apache2/kvmdash_error.log
tail -f /var/log/apache2/kvmdash_access.log
```

## Struktur

- Frontend (vite): `/var/www/kvmdash/frontend/dist`
- Backend (Symfony): `/var/www/kvmdash/www/html/backend/public`
- API Endpunkt: `http://kvmdash/api`

## Fehlerbehebung

Bei Fehlern überprüfen Sie:
1. Ob alle benötigten Module aktiviert sind
2. Die Berechtigungen der Verzeichnisse
3. Die Apache Error Logs
4. Ob die Symlinks korrekt gesetzt sind

## Sicherheitshinweise

- Die CORS-Header sind aktuell für alle Ursprünge (`*`) konfiguriert
- In Produktionsumgebungen sollten die CORS-Header eingeschränkt werden
- SSL/TLS sollte für Produktionsumgebungen konfiguriert werden
