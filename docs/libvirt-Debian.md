# 1. Einführung in Libvirt

**Libvirt** ist eine Open-Source-Bibliothek und ein Toolset zur Verwaltung von virtuellen Maschinen und Ressourcen wie Speicher und Netzwerken. Es bietet eine einheitliche Schnittstelle für verschiedene Virtualisierungslösungen auf Linux und erleichtert die Automatisierung von Virtualisierungsaufgaben. Ideal für Admins und Entwickler, die VMs effizient verwalten wollen.

**Warum Libvirt ?**

* Unterstützt verschiedene Virtualisierungstechnologien wie KVM, QEMU und Xen.
* Einheitliche API für verschiedene Virtualisierungstools.
* Ermöglicht einfache Verwaltung von VMs, Netzwerken und Speicher per CLI oder GUI.
* Weit verbreitet, mit aktiver Community und regelmäßigen Updates.

# 2. Installation von Libvirt

Öffne ein Terminal und installiere Libvirt mit:

```bash
apt update
apt install libvirt-daemon-system libvirt-clients qemu-kvm
```

Nach der Installation startet der Libvirt-Dienst normalerweise automatisch. Du kannst das mit folgendem Befehl prüfen:<br/>
`systemctl status libvirtd`

Falls der Dienst nicht läuft, kannst du ihn starten mit:<br/>
`systemctl start libvirtd`

# 3. Konfiguration von Libvirt

**Konfigurationsdateien verstehen**:<br/>
Libvirt verwendet verschiedene Konfigurationsdateien, um seine Einstellungen zu speichern. Die wichtigsten Dateien sind:
* **/etc/libvirt/libvirtd.conf:**<br/>
Diese Datei enthält die allgemeinen Konfigurationseinstellungen für den Libvirt-Daemon.
* **/etc/libvirt/qemu.conf:**<br/> Hier werden spezifische Einstellungen für die QEMU-Hypervisor-Backend-Konfiguration gespeichert.
* **/etc/libvirt/qemu/*.xml:**<br/> Diese XML-Dateien enthalten die Konfigurationen für jede virtuelle Maschine.

**Netzwerkkonfiguration:**<br/> 
Libvirt bietet verschiedene Netzwerkoptionen für virtuelle Maschinen, einschließlich NAT, Bridged Networking und Host-Only Networking. Die Konfiguration erfolgt normalerweise über das Tool virsh oder die grafische Benutzeroberfläche virt-manager.

**Konfiguration von Libvirt für die Bridge:**<br/>
1. Öffne eine Terminal-Sitzung auf deinem Linux-System.

2. Erstelle eine XML-Datei für die Netzwerkkonfiguration. 
   Du kannst dafür einen Texteditor deiner Wahl verwenden. Zum Beispiel:<br/> `nano /etc/libvirt/qemu/networks/bridge.xml`
3. Füge den folgenden Inhalt in die Datei ein:<br/>
    ```xml
    <network>
      <name>br0</name>
      <forward mode="bridge"/>
      <bridge name="br0"/>
    </network>
    ```
4. Speichere die Datei und schließe den Texteditor.
5. Definiere das Netzwerk mit dem Befehl. <br/>
    `virsh net-define /etc/libvirt/qemu/networks/bridge.xml`
6. Aktiviere das Netzwerk automatisch beim Systemstart<br/>
    `virsh net-autostart br0`
7. Starte das Netzwerk<br/>
    `virsh net-start br0`
8. Überprüfen, ob das Libvirt-Netzwerk korrekt definiert und gestartet ist:
    `virsh net-list --all`

Damit ist die Bridge erfolgreich für die Verwendung durch Libvirt konfiguriert. Du kannst nun virtuelle Maschinen erstellen und sie so konfigurieren, dass sie das br0-Netzwerk verwenden.

# 4. Verwaltung von virtuellen Maschinen mit Libvirt
Libvirt bietet verschiedene Möglichkeiten zur Verwaltung von VMs. Hier schauen wir uns die grundlegenden Befehle zum Erstellen, Starten, Stoppen und Neustarten von VMs an.

**Erstellen einer virtuellen Maschine**
Du kannst entweder die Befehlszeilenschnittstelle (virsh) nutzen oder eine grafische Oberfläche wie virt-manager. In diesem Tutorial konzentrieren wir uns auf die CLI.

Hier ein Beispiel, um eine VM mit Libvirt zu erstellen:<br/>
```bash
virt-install \
--name mydebian \
--memory 4096 \
--vcpus 4 \
--disk size=20 \
--location /srv/iso/debian-12.5.0-amd64-netinst.iso \
--network bridge=br0 \
--os-variant debiantesting \
--graphics none \
--extra-args "console=ttyS0"
```

Der folgende Befehl erstellt eine VM mit dem Namen "mydebian", 4 GB RAM, 4 vCPUs, einer 20 GB großen virtuellen Festplatte, einem ISO-Image zur Installation und einer Netzwerkverbindung über die Bridge br0.

**⚠ Wichtig:** Ersetze `/root/iso/debian-12.5.0-amd64-netinst.iso` mit dem tatsächlichen Pfad zu deinem ISO-Image!

Während die VM startet, kannst du die Installation im Terminal verfolgen und Anweisungen zur Konfiguration befolgen. Nach Abschluss der Installation sollte die VM über br0 ins Netzwerk kommen und einsatzbereit sein. 🚀

## Grundlagen virtuellen Maschinen mit Libvirt

Nachdem du eine virtuelle Maschine erstellt hast, wirst du möglicherweise feststellen, dass du sie starten, stoppen oder neu starten musst. Hier sind die grundlegenden Schritte, um diese Aktionen mit Libvirt durchzuführen:

### Starten einer virtuellen Maschine:<br/>
Um eine virtuelle Maschine zu starten, verwende den Befehl<br/>
`virsh start <name_der_virtuellen_maschine>`. 

Zum Beispiel:<br/>
`virsh start mydebian`

### Stoppen einer virtuellen Maschine:<br/>
Um eine laufende virtuelle Maschine zu stoppen, verwende den Befehl<br/>
`virsh shutdown <name_der_virtuellen_maschine>`. 

Zum Beispiel:<br/>
`virsh shutdown mydebian`

### Neustarten einer virtuellen Maschine:<br/>
Um eine virtuelle Maschine neu zu starten, verwende den Befehl<br/>
`virsh reboot <name_der_virtuellen_maschine>`. 

Zum Beispiel:<br/>
`virsh reboot mydebian`

 ### Erstellen eines Snapshots:<br/>
 Um einen Snapshot einer virtuellen Maschine zu erstellen, verwende den Befehl<br/>
  `virsh snapshot-create-as <name_der_virtuellen_maschine> <snapshot_name>`. 
 
 Zum Beispiel:<br/>
` virsh snapshot-create-as mydebian snapshot1`

### Wiederherstellen eines Snapshots: <br/>
Um eine virtuelle Maschine aus einem Snapshot wiederherzustellen, verwende den Befehl<br/>
`virsh snapshot-revert <name_der_virtuellen_maschine> <snapshot_name>`. 

Zum Beispiel:<br/>
`virsh snapshot-revert mydebian snapshot1`

⚠ Bitte beachte, dass während der Wiederherstellung einer virtuellen Maschine aus einem Snapshot alle Änderungen seit dem Zeitpunkt des Snapshots verloren gehen.

# 5. Verwendung von virt-manager<br/>
virt-manager ist eine grafische Benutzeroberfläche (GUI), die es ermöglicht, virtuelle Maschinen zu verwalten und zu überwachen. Mit virt-manager kannst du virtuelle Maschinen erstellen, starten, stoppen, überwachen und vieles mehr, alles über eine benutzerfreundliche Oberfläche.

Um virt-manager zu verwenden, installiere es auf deinem System mit:<br/>
`apt install virt-manager`

Starte dann virt-manager mit: `virt-manager`
