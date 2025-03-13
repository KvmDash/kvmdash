# Installation von KVM unter Debian 12 "Bookworm"
In diesem Tutorial zeige ich dir, wie du KVM unter Debian 12 installierst. KVM (Kernel-based Virtual Machine) ist eine Virtualisierungslösung für Linux, mit der du mehrere virtuelle Maschinen auf einem einzigen Rechner laufen lassen kannst. Das Ganze nutzt die Virtualisierungserweiterungen von Intel (VT-x) oder AMD (AMD-V), um eine schnelle und effiziente Umgebung bereitzustellen.

Mit KVM kannst du verschiedene Betriebssysteme und Anwendungen sauber voneinander getrennt in virtuellen Maschinen ausführen. Das bringt mehr Flexibilität, bessere Ressourcennutzung und macht dein Setup insgesamt skalierbarer. Lass uns direkt loslegen! 🚀

# Systemvoraussetzungen
Dieses Howto setzt voraus, dass du Debian 12 (Bookworm) mit einer minimalen Installation ohne grafische Oberfläche verwendest.

Bevor du KVM installierst, solltest du sicherstellen, dass dein Prozessor die nötigen Virtualisierungserweiterungen unterstützt. KVM läuft nur auf Intel- oder AMD-Prozessoren, die VT-x (bei Intel) oder AMD-V (bei AMD) bieten.

Um das zu überprüfen, kannst du folgendes Shell-Skript ausführen. Es sucht nach den passenden CPU-Flags (vmx für Intel, svm für AMD). Falls diese vorhanden sind, bedeutet das, dass dein Prozessor die Hardware-Virtualisierung unterstützt. Zusätzlich prüft das Skript, ob die notwendigen Kernelmodule für KVM bereits geladen sind.


```bash
#!/bin/bash

# Überprüfen, ob die CPU die Virtualisierung unterstützt
if grep -E "(vmx|svm)" /proc/cpuinfo > /dev/null; then
    echo "Die CPU unterstützt KVM."
else
    echo "Die CPU unterstützt kein KVM."
    exit 1
fi

# Überprüfen, ob die KVM-Module geladen sind
if lsmod | grep kvm > /dev/null; then
    echo "Die KVM-Module sind geladen."
else
    echo "Die KVM-Module sind nicht geladen. Bitte stellen Sie sicher, dass Sie das richtige Kernelmodul 
          für Ihre CPU und Virtualisierungsumgebung installiert haben und versuchen Sie es erneut."
    exit 1
fi

exit 0
```

Speicher das Skript in einer Datei mit der Endung .sh, zum Beispiel `cpu_kvm_check.sh`. Anschließend kannst du es ausführen, um zu checken, ob deine CPU KVM unterstützt.

Neben den CPU-Flags braucht die KVM-Installation auf Debian 12 auch das Laden von Kernelmodulen. Dazu gehört das Modul `kvm.ko`, das die Kernvirtualisierungsinfrastruktur bereitstellt, sowie ein prozessorspezifisches Modul wie `kvm-intel.ko` (bei Intel) oder `kvm-amd.ko` (bei AMD). Diese Module müssen verfügbar und geladen werden, damit du KVM nutzen kannst. Achte darauf, dass deine Linux-Kernelkonfiguration diese Module enthält und sie problemlos geladen werden können.

Wichtig: Die Virtualisierungsfunktionen müssen auch im BIOS/UEFI aktiviert sein. Falls die CPU-Flags fehlen oder die Virtualisierung im BIOS/UEFI deaktiviert ist, lässt sich KVM leider nicht verwenden.

# Konfiguration einer Netzwerkbrücke (Bridge)
Eine Netzwerkbrücke ermöglicht es virtuellen Maschinen, direkt mit dem physischen Netzwerk zu kommunizieren, als wären sie eigenständige Maschinen. Das ist besonders praktisch, wenn du möchtest, dass deine virtuellen Maschinen von anderen Geräten im Netzwerk erreicht werden können oder wenn du bestimmte Netzwerkdienste darauf bereitstellen willst.

Um eine Netzwerkbrücke unter Debian einzurichten, geh einfach wie folgt vor:

1. Ermittle deine physische Schnittstelle:<br/>
    Benutze den Befehl `ip -f inet a s`, um deine physische Schnittstelle zu finden. Diese könnte zum Beispiel `enp4s0 `oder `eth0 `heißen.

2. Installiere bridge-utils:<br/>
    Stell sicher, dass das Paket bridge-utils installiert ist, da du es zur Verwaltung von Netzwerkbrücken brauchst. Installier es einfach mit diesem Befehl:
    `apt-get install bridge-utils`

3. Aktualisiere die Datei `/etc/network/interfaces`:<br/>
    Achte darauf, dass in der Datei nur die Loopback-Schnittstelle (lo) aktiv ist. Alles, was mit deiner physischen Schnittstelle (z.B. enp4s0) zu tun hat, sollte entfernt werden.
    Beispielinhalt für die Datei  `/etc/network/interfaces`:
    ```bash
    # This file describes the network interfaces available on your system
    # and how to activate them. For more information, see interfaces(5).
    source /etc/network/interfaces.d/*
    # The loopback network interface
    auto lo
    iface lo inet loopback
    ```

4. Konfigurieren der Brücke (br0) in der Datei `/etc/network/interfaces.d/br0`:
    Öffne oder erstelle eine Datei und füge die passende Konfiguration hinzu.
    Beispielinhalt für die Datei, je nachdem, ob du DHCP oder eine statische IP nutzen möchtest:
    ## DHCP-Konfiguration:
    ```bash
    # DHCP Konfigurationsdatei für br0 ##
    auto br0
    # Bridge-Konfiguration
    iface br0 inet dhcp
    bridge_ports enp4s0
    ```

    ## Statische IP-Konfiguration:
    ```bash
    ## Statische IP Konfigurationsdatei für br0 ##
    auto br0
    
    # Bridge-Konfiguration
    iface br0 inet static
        address 192.168.0.200
        broadcast 192.168.0.255
        netmask 255.255.255.0
        gateway 192.168.0.1
        # Wenn das Paket resolvconf installiert ist, solltest su die resolv.conf-Konfigurationsdatei
        # nicht manuell bearbeiten. Lege hier die Namensserver fest.
        # dns-nameservers 192.168.2.254
        # Wenn du mehrere Schnittstellen wie eth0 und eth1 haben solltest
        # bridge_ports eth0 eth1
        bridge_ports enp4s0
        bridge_stp off       # Spanning Tree Protocol deaktivieren
        bridge_waitport 0    # Keine Verzögerung, bis ein Port verfügbar wird
        bridge_fd 0          # Keine Weiterleitungsverzögerung
    ```
# Neustarten des Netzwerkdienstes in Linux:

Bevor du den Netzwerkdienst neu startest, solltest du sicherstellen, dass die Firewall deaktiviert ist. Denn die Firewall-Regel könnte noch auf die alte Schnittstelle (z.B. `enp4s0`) verweisen. Nach dem Neustart musst du dann die Firewall-Regel für die neue Schnittstelle (br0) anpassen.

1. Verwende den folgenden Befehl, um den Netzwerkdienst neu zu starten:<br/>
`systemctl restart networking`

2. Überprüfe anschließend, ob der Dienst erfolgreich neu gestartet wurde:<br/>
`systemctl status networking`

# Installation

Sobald du bestätigt hast, dass dein System Virtualisierung unterstützt, kannst du KVM auf Debian 12 installieren. Einfach den folgenden Befehl ausführen:

```bash
apt install qemu-kvm qemu-utils libvirt-daemon-system virtinst bridge-utils
```
* qemu-kvm:<br/>
    QEMU ist ein Open-Source-Virtualisierer, mit dem du virtuelle Maschinen auf deinem Host-System laufen lassen kannst. Das Paket qemu-system enthält die Hauptkomponenten von QEMU, die du zum Erstellen und Ausführen von VMs brauchst.

* qemu-utils:<br/>
    Dieses Paket enthält nützliche Hilfsprogramme und Tools, die mit QEMU verbunden sind. Dazu gehören Funktionen wie die Konvertierung von Disk-Images oder das Erstellen von Snapshots.

* libvirt-daemon-system:<br/>
    Libvirt ist ein Toolkit und eine API zur Verwaltung von virtuellen Maschinen und anderen Virtualisierungsressourcen. Das Paket libvirt-daemon-system enthält den libvirt-Daemon, der die Hauptkomponente von libvirt ist und die Verwaltung von VMs ermöglicht.

* virtinst:<br/>
    Das Paket virtinst ist ein Kommandozeilen-Tool zum Erstellen und Verwalten von virtuellen Maschinen auf Systemen, die libvirt verwenden. Es vereinfacht das Erstellen neuer VMs durch eine einfache Befehlsschnittstelle.

* bridge-utils:<br/>
ist ein Paket, das Tools zur Verwaltung von Netzwerkbrücken bereitstellt. 

Mit der Installation dieser Pakete bekommst du eine komplette Virtualisierungsumgebung auf deinem Debian 12-System, mit der du VMs erstellen, verwalten und ausführen kannst.

Weiter geht es mit den Grundlagen von Libvirt:
[Einführung in libvirt](https://github.com/zerlix/Howtos/blob/main/libvirt_Debian.md)
