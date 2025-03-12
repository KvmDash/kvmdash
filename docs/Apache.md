# Apache Konfiguration für KVM Dashboard

Diese Anleitung beschreibt die Apache-Konfiguration für das KVM Dashboard Projekt.

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

    # Frontend Routes zu index.html weiterleiten
    <Directory /var/www/kvmdash/frontend/dist>
        AllowOverride None
        Require all granted
        FallbackResource /index.html
    </Directory>

    # Backend API unter /api
    Alias /api /var/www/kvmdash/backend/public
    <Directory /var/www/kvmdash/backend/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
        
        Header set Access-Control-Allow-Origin "*"
        Header set Access-Control-Allow-Methods "GET,POST,PUT,DELETE,PATCH,OPTIONS"
        Header set Access-Control-Allow-Headers "Content-Type,Authorization,X-Requested-With"
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/kvmdash_error.log
    CustomLog ${APACHE_LOG_DIR}/kvmdash_access.log combined
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

- Frontend (Vue.js): `/home/zerlix/www/html/frontend/dist`
- Backend (Symfony): `/home/zerlix/www/html/backend/public`
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