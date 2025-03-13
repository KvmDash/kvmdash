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


## Features

### VM Verwaltung
* Erstellen, Löschen und Konfigurieren von VMs und Containern über die Weboberfläche.
* Nutzung von Vorlagen für die schnelle und standardisierte Erstellung von VMs.

### Systemmonitoring
* Echtzeitüberwachung von Ressourcen wie CPU, Arbeitsspeicher, Festplattenauslastung und weiteren wichtigen Systemmetriken.
* Übersichtliche Darstellung der Systemleistung für eine optimale Kontrolle und Fehleranalyse.


## Videos



https://github.com/user-attachments/assets/ec76e8fa-f9b1-487d-87a8-6d370dbfb73c



## Installation

### Voraussetzung

* Node.js 18.x oder neuer
* npm 9.x oder neuer
* Composer 2.x
* libvirt und libvirt-dev

#### 1. Installation der Voraussetzungen auf Ubuntu/Debian:
```bash
# Node.js und npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# PHP und Extensions
sudo apt install php8.2 php8.2-xml php8.2-curl php8.2-mysql php8.2-mbstring php8.2-zip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# MySQL
sudo apt install mysql-server

# Libvirt
sudo apt install libvirt-dev libvirt-daemon-system php-libvirt-php
```

#### 2. Repository klonen:
```bash
git clone https://github.com/KvmDash/kvmdash.git kvmdash
cd kvmdash
```

####  3. Submodule initialisieren und aktualisieren (für [Spice Client](https://gitlab.freedesktop.org/spice/spice-html5))
```bash
git submodule update --init --recursive
```


## Backend

#### 1. Umgebungsvariablen konfigurieren:
```bash
# .env.local erstellen
cp .env .env.local

# .env.local anpassen (wichtige Einstellungen):
# APP_ENV=dev
# APP_SECRET=IhrGeheimesSecret
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/kvmdash"
# JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
# JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
# JWT_PASSPHRASE=IhrJWTPassphrase
```

#### 2. JWT Schlüssel generieren:
```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

#### 3. Datenbank einrichten:
```bash
# Datenbank erstellen
php bin/console doctrine:database:create

# Migrations ausführen
php bin/console doctrine:migrations:migrate
```

#### 4. Entwicklungsserver starten:
```bash
# Mit PHP's eingebautem Server
php -S localhost:8000 -t public/

# Oder mit Symfony CLI
symfony server:start
```

### 5. API Dokumentation
Die API-Dokumentation ist nach dem Start des Servers verfügbar unter:
```
https://127.0.0.1:8001/api/docs
```


## Frontend 

#### 1. SPICE HTML5 Client konfigurieren
```bash
cd frontend/src/assets/spice-html5
cp package.json.in package.json
sed -i 's/VERSION/0.3/g' package.json
```

####  2. Dependencies installieren:
```bash
npm install
```

####  3. Entwicklungsserver starten:
```bash
npm run dev
```

#### 4. Vite Konfiguration anpassen:
Öffne die Datei `src/config.ts` und füge die folgende Konfiguration hinzu, um den Entwicklungsserver zu starten und API-Anfragen an das Backend weiterzuleiten:

```javascript
const BACKEND_PORT = 8000;
export const BACKEND_HOST = '192.168.0.200';
```





