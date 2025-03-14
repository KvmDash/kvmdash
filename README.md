# Projektbeschreibung: KVMDash

<table style="border-collapse: collapse; width: 100%;">
    <tr>
        <td style="width: 150px; padding: 10px; vertical-align: middle;">
            <img src="frontend/src/assets/kvmdash.svg" alt="KvmDash Logo" style="max-width: 100%;">
        </td>
        <td style="padding: 10px; vertical-align: middle;">
            KVMDash ist eine Webanwendung, die die Verwaltung von Virtual Machines (VMs) auf Linux-Systemen ermöglicht. 
            Mit einer benutzerfreundlichen Oberfläche erleichtert KVMDash die Administration und Überwachung von Virtualisierungsumgebungen.
        </td>
    </tr>
</table>

## 📑 Inhaltsverzeichnis
- [Features](#features)
- [Demo-Videos](#demo-videos)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Installation](#installation)
  - [1. Systemvoraussetzungen installieren](#1-systemvoraussetzungen-installieren)
  - [2. KVMDash installieren](#2-kvmdash-installieren)
  - [3. Backend einrichten](#3-backend-einrichten)
  - [4. Frontend einrichten](#4-frontend-einrichten)
  - [5. Webserver einrichten](#5-webserver-einrichten)
  - [6. Direktes Testen ohne Apache (Entwicklungsumgebung)](#6-direktes-testen-ohne-apache-entwicklungsumgebung)
- [Dokumentation](#dokumentation)

## Features

### VM Verwaltung
* Erstellen, Löschen und Konfigurieren von VMs und Containern über die Weboberfläche.
* Nutzung von Vorlagen für die schnelle und standardisierte Erstellung von VMs.

### Systemmonitoring
* Echtzeitüberwachung von Ressourcen wie CPU, Arbeitsspeicher, Festplattenauslastung und weiteren wichtigen Systemmetriken.
* Übersichtliche Darstellung der Systemleistung für eine optimale Kontrolle und Fehleranalyse.

## Demo-Videos

https://github.com/user-attachments/assets/ec76e8fa-f9b1-487d-87a8-6d370dbfb73c

## Systemvoraussetzungen

* Node.js 18.x oder neuer
* npm 9.x oder neuer
* Composer 2.x
* KVM und libvirt
* Apache Webserver mit PHP 8.2

## Installation

### 1. Systemvoraussetzungen installieren

#### KVM/QEMU und Libvirt
Für die vollständige Anleitung zur Installation von KVM unter Debian, siehe [KVM Installation Guide](docs/kvm-Debian.md).

```bash
# Kurzversion: KVM und Libvirt installieren
apt update
apt install qemu-kvm qemu-utils libvirt-daemon-system virtinst bridge-utils
```

Detaillierte Anleitung zur Konfiguration von Libvirt: [Libvirt Konfiguration](docs/libvirt-Debian.md)

#### Node.js und npm
```bash
# Node.js und npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### PHP und Extensions
```bash
# PHP und Extensions
sudo apt install php8.2 php8.2-xml php8.2-curl php8.2-mysql php8.2-mbstring php8.2-zip
```

#### Composer
```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Datenbank
KVMDash verwendet Doctrine ORM und unterstützt verschiedene Datenbanksysteme:

```bash
# MySQL
sudo apt install mysql-server

# Oder PostgreSQL
# sudo apt install postgresql

# SQLite wird ohne zusätzliche Installation unterstützt
```

#### Webserver
Apache Konfiguration für KVMDash: [Apache Setup Guide](docs/apache-Debian.md)
```bash
# Apache und benötigte Module
sudo apt install apache2
sudo a2enmod headers rewrite alias
```

### 2. KVMDash installieren

```bash
# Repository klonen
git clone https://github.com/KvmDash/kvmdash.git /var/www/kvmdash
cd /var/www/kvmdash

# Submodule initialisieren und aktualisieren (für Spice Client)
git submodule update --init --recursive
```

### 3. Backend einrichten

```bash
# Im Backend-Verzeichnis
cd /var/www/kvmdash

# .env.local erstellen
cp .env .env.local

# .env.local anpassen (wichtige Einstellungen):
# APP_ENV=dev
# APP_SECRET=IhrGeheimesSecret
# Datenbankverbindung (wählen Sie eine der folgenden Optionen):
# MySQL: DATABASE_URL="mysql://user:password@127.0.0.1:3306/kvmdash"
# PostgreSQL: DATABASE_URL="postgresql://user:password@127.0.0.1:5432/kvmdash?serverVersion=15&charset=utf8"
# SQLite: DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
# JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
# JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
# JWT_PASSPHRASE=IhrJWTPassphrase

# JWT Schlüssel generieren
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Datenbank einrichten
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Frontend einrichten

```bash
# SPICE HTML5 Client konfigurieren
cd /var/www/kvmdash/frontend/src/assets/spice-html5
cp package.json.in package.json
sed -i 's/VERSION/0.3/g' package.json

# Ins Frontend-Verzeichnis wechseln
cd /var/www/kvmdash/frontend

# Dependencies installieren
npm install

# Vite Konfiguration anpassen
# Öffne die Datei src/config.ts und passe die Backend-Einstellungen an
```

Beispiel für `src/config.ts`:
```javascript
/// Testumgebung
// export const BACKEND_PORT = 8000; // Port des Backends
// export const BACKEND_HOST = 'localhost'; // Hostname/IP-Adresse des Backends

export const BACKEND_PORT = 80; // Port des Backends
export const BACKEND_HOST = 'kvmdash'; // Hostname/IP-Adresse des Backends
```

### 5. Webserver einrichten

Kopiere die Apache-Konfiguration aus dem docs-Verzeichnis:

```bash
cp /var/www/kvmdash/docs/001-kvmdash.conf /etc/apache2/sites-available/
sudo a2ensite 001-kvmdash.conf
sudo systemctl reload apache2
```

Vollständige Anleitung zur Apache-Konfiguration: [Apache Einrichtung](docs/apache-Debian.md)

### 6. Direktes Testen ohne Apache (Entwicklungsumgebung)

Für schnelles Testen oder Entwicklung kann KVMDash auch ohne Apache ausgeführt werden:

#### Backend mit Symfony Development Server

```bash
# Im Backend-Verzeichnis
cd /var/www/kvmdash/backend

# Symfony Development Server starten (alle IPs erlauben)
symfony server:start --allow-all-ip
```

Der Backend-Server ist nun unter `http://your-ip:8000` erreichbar.

#### Frontend mit Vite Development Server

```bash
# In einem neuen Terminal, im Frontend-Verzeichnis
cd /var/www/kvmdash/frontend

# Vite Dev Server starten
npm run dev
```

Der Frontend-Server ist standardmäßig unter `http://localhost:5173` verfügbar.

> **Hinweis:** Diese Methode ist nur für Entwicklung und Tests gedacht. Für Produktivumgebungen wird die Apache-Konfiguration empfohlen.

## Dokumentation

Die API-Dokumentation ist nach dem Start des Servers verfügbar unter:
```
https://your-server/api/docs
```

Weitere Dokumentation:
- [KVM Installation und Konfiguration](docs/kvm-Debian.md)
- [Libvirt Einrichtung und Verwaltung](docs/libvirt-Debian.md)
- [Apache Webserver Konfiguration](docs/apache-Debian.md)

## TurnKey Linux Kompatibilität

KVMDash arbeitet nahtlos mit [TurnKey Linux](http://mirror.turnkeylinux.org/turnkeylinux/images/iso/) Images zusammen. Mit TurnKey Linux erhalten Sie Zugriff auf über 100 vorgefertigte, gebrauchsfertige Server-Appliances, die auf Debian basieren.

### Vorteile der Integration:
- **Schnelle Bereitstellung**: Sofortige Einsatzbereitschaft spezialisierter Server ohne aufwendige Konfiguration
- **Breites Angebot**: Von Webserver-Stacks (LAMP, LEMP) über CMS (WordPress, Drupal) bis hin zu Kollaborationstools
- **Sicherheit**: Regelmäßig aktualisierte, gehärtete Images mit automatischen Sicherheitsupdates

### Verwendung von TurnKey Images in KVMDash:
1. Laden Sie das gewünschte TurnKey Image von der offiziellen Webseite herunter
2. Importieren Sie das Image in KVMDash als VM-Vorlage
3. Erstellen Sie neue VMs basierend auf diesen Vorlagen mit nur wenigen Klicks

TurnKey Images bieten eine ideale Ergänzung zu KVMDash, um schnell produktive Serverumgebungen bereitzustellen, ohne aufwendige manuelle Konfigurationen durchführen zu müssen.





