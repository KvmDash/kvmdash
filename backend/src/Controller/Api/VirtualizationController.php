<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

use App\Dto\VirtualMachine;
use App\Dto\VirtualMachineAction;

#[ApiResource(
    operations: [
        new GetCollection(
            name: 'get_domains',
            uriTemplate: '/virt/domains',
            controller: self::class . '::listDomains',
            read: false,
            output: VirtualMachine::class,
        ),
        new GetCollection(
            name: 'get_domain_status',
            uriTemplate: '/virt/domains/status',
            controller: self::class . '::getDomainStatus',
            read: false
        ),
        new GetCollection(
            name: 'get_domain_details',
            uriTemplate: '/virt/domain/{name}/details',
            controller: self::class . '::getDomainDetails',
            read: false
        ),
        // In den ApiResource annotations
        new GetCollection(
            name: 'get_spice_connection',
            uriTemplate: '/virt/domain/{name}/spice',
            controller: self::class . '::getSpiceConnection',
            read: false
        ),
        new Post(
            name: 'start_domain',
            uriTemplate: '/virt/domain/{name}/start',
            controller: self::class . '::startDomain',
            read: false,
            output: VirtualMachineAction::class,
        ),
        new Post(
            name: 'stop_domain',
            uriTemplate: '/virt/domain/{name}/stop',
            controller: self::class . '::stopDomain',
            read: false,
            output: VirtualMachineAction::class,
        ),
        new Post(
            name: 'reboot_domain',
            uriTemplate: '/virt/domain/{name}/reboot',
            controller: self::class . '::rebootDomain',
            read: false,
            output: VirtualMachineAction::class,
        ),
        new Post(
            name: 'delete_domain',
            uriTemplate: '/virt/domain/{name}/delete',
            controller: self::class . '::deleteDomain',
            read: false,
            output: VirtualMachineAction::class,
        ),
        new Post(
            name: 'create_domain',
            uriTemplate: '/virt/domain/create',
            controller: self::class . '::createDomain',
            read: false,
            output: VirtualMachineAction::class,
        ),

    ]
)]



/**
 * Controller für die Verwaltung virtueller Maschinen über libvirt
 * 
 * Diese Klasse stellt REST-API Endpunkte bereit für:
 * - Auflisten und Status-Abfrage von VMs
 * - Start/Stop/Reboot Operationen
 * - Löschen von VMs inkl. Storage
 *
 * Technische Details:
 * - Nutzt libvirt PHP Extension für QEMU/KVM Zugriff
 * - Kommuniziert mit lokalem Hypervisor (qemu:///system)
 * - Unterstützt UEFI/NVRAM und QEMU Guest Agent
 * - Mehrsprachige Fehlermeldungen (DE/EN)
 *
 * @package App\Controller\Api
 */
class VirtualizationController extends AbstractController
{
    private $connection;
    private $translator;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)

    {
        $this->translator = $translator;

        $request = $requestStack->getCurrentRequest();
        if ($request) {
            $locale = $request->getPreferredLanguage(['en', 'de']) ?? 'de';
            $request->setLocale($locale);
        }


        if (!extension_loaded('libvirt')) {
            throw new \Exception($this->translator->trans('error.libvirt_not_installed'));
        }
    }

    /**
     * Stellt eine Verbindung zum lokalen QEMU/KVM Hypervisor her
     * 
     * Diese private Methode wird von allen API-Endpunkten verwendet, um:
     * - Eine Verbindung zum Hypervisor herzustellen falls noch keine existiert
     * - Die Verbindung wiederzuverwenden wenn sie bereits besteht
     * - Die korrekte URI für den lokalen QEMU-Hypervisor zu verwenden
     *
     * Verbindungsdetails:Im Virt
     * - URI: 'qemu:///system' für den System-Mode
     * - Authentifizierung: Über System-Berechtigungen
     * 
     * @throws \Exception wenn die Verbindung nicht hergestellt werden kann
     */
    private function connect(): void
    {
        if (!$this->connection) {

            // Mit lokalem Hypervisor verbinden
            $this->connection = libvirt_connect('qemu:///system', false);
            if (!$this->connection) {
                throw new \Exception($this->translator->trans('error.libvirt_connection_failed') . libvirt_get_last_error());
            }
        }
    }

    /**
     * Listet alle verfügbaren virtuellen Maschinen auf
     * 
     * Diese Methode liefert eine Liste aller definierten VMs mit Basis-Informationen:
     * - ID und Name der VM
     * - Aktueller Status (running, stopped, etc.)
     * - Zugewiesener und maximaler RAM
     * - Anzahl virtueller CPUs
     *
     * Das Format der Rückgabe ist für die Sidebar optimiert:
     * {
     *   "domains": [
     *     {
     *       "id": "vm-1",
     *       "name": "vm-1", 
     *       "state": 1,         // 1=running, 5=stopped
     *       "memory": 4194304,  // Aktueller RAM in KB
     *       "maxMemory": 8388608, // Maximaler RAM in KB
     *       "cpuCount": 2
     *     }
     *   ]
     * }
     * 
     * @return JsonResponse Liste aller VMs mit Basis-Informationen
     * @throws \Exception Bei Verbindungsproblemen zum Hypervisor
     */
    public function listDomains(): JsonResponse
    {
        try {
            $this->connect();

            $domains = [];
            $activeDomains = libvirt_list_domains($this->connection);

            foreach ($activeDomains as $domainId) {
                $domain = libvirt_domain_lookup_by_name($this->connection, $domainId);

                if ($domain) {
                    $info = libvirt_domain_get_info($domain);
                    $domains[] = new VirtualMachine(
                        id: $domainId,
                        name: $domainId,
                        state: $info['state'] ?? 0,
                        memory: $info['memory'] ?? 0,
                        maxMemory: $info['maxMem'] ?? 0,
                        cpuCount: $info['nrVirtCpu'] ?? 0
                    );
                }
            }


            return $this->json(['domains' => $domains]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Startet eine virtuelle Maschine
     *
     * Diese Methode startet eine VM, die sich im gestoppten Zustand befindet.
     * Der Start erfolgt ohne Parameter und entspricht einem normalen Bootvorgang.
     *
     * Wichtige Hinweise:
     * - Die VM muss definiert und nicht bereits laufend sein
     * - Ausreichend Systemressourcen müssen verfügbar sein
     * - QEMU Guest Agent startet erst nach vollständigem Boot
     *
     * @param string $name Name der virtuellen Maschine
     * @return JsonResponse Status der Start-Operation
     * @throws \Exception Bei Verbindungsproblemen oder wenn die Domain nicht gefunden wird
     */

    public function startDomain(string $name): JsonResponse
    {
        try {
            $this->connect();
            $domain = libvirt_domain_lookup_by_name($this->connection, $name);

            if (!$domain) {
                return $this->json(['error' => $this->translator->trans('error.libvirt_domain_not_found')], 404);
            }

            $result = libvirt_domain_create($domain);

            return $this->json(new VirtualMachineAction(
                success: $result !== false,
                domain: $name,
                action: 'start',
                error: $result === false ? libvirt_get_last_error() : null
            ));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Stoppt eine virtuelle Maschine
     * 
     * Diese Methode unterstützt zwei Stop-Modi:
     * 1. Graceful Shutdown (Standard)
     *    - Sendet ACPI-Signal zum Herunterfahren
     *    - Erlaubt sauberes Beenden von Diensten
     *    - VM kann Shutdown verweigern
     * 
     * 2. Force Stop (wenn force=true)
     *    - Sofortiges Beenden der VM
     *    - Vergleichbar mit Stromkabel ziehen
     *    - Kann zu Datenverlust führen
     * 
     * Request-Body Format:
     * {
     *   "force": true|false  // Optional, default false
     * }
     * 
     * @param string $name Name der virtuellen Maschine
     * @param Request $request HTTP-Request mit force-Option
     * @return JsonResponse Status der Stop-Operation
     * @throws \Exception Bei Verbindungsproblemen oder wenn die Domain nicht gefunden wird
     */
    public function stopDomain(string $name, Request $request): JsonResponse
    {
        try {
            $this->connect();
            $domain = libvirt_domain_lookup_by_name($this->connection, $name);

            if (!$domain) {
                return $this->json(['error' => $this->translator->trans('error.libvirt_domain_not_found')], 404);
            }

            $data = json_decode($request->getContent(), true);
            $force = isset($data['force']) && $data['force'] === true;

            $result = false;
            if ($force) {
                $result = libvirt_domain_destroy($domain);
            } else {
                $result = libvirt_domain_shutdown($domain);
            }

            return $this->json(new VirtualMachineAction(
                success: $result !== false,
                domain: $name,
                action: $force ? $this->translator->trans('libvirt_domain_force_stop') :  $this->translator->trans('libvirt_domain_graceful_stop'),
                error: $result === false ? libvirt_get_last_error() : null
            ));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Holt den aktuellen Status aller virtuellen Maschinen
     * 
     * Diese Methode liefert detaillierte Statusinformationen für alle VMs:
     * - Aktueller Zustand (running, stopped, etc.)
     * - Zugewiesener RAM (balloon)
     * - Anzahl virtueller CPUs
     * - IP-Adresse (falls verfügbar via QEMU Guest Agent)
     *
     * Das Format der Rückgabe ist für das Frontend optimiert:
     * {
     *   "vm-name": {
     *     "state.state": "1",        // 1=running, 5=stopped
     *     "balloon.current": "4096",  // RAM in KB
     *     "vcpu.current": "2",       // Anzahl vCPUs
     *     "ip": "192.168.1.100"      // IP oder leer
     *   }
     * }
     * 
     * @return JsonResponse Status aller VMs als assoziatives Array
     * @throws \Exception Bei Verbindungsproblemen zum Hypervisor
     */
    public function getDomainStatus(): JsonResponse
    {
        try {
            $this->connect();
            $domains = [];
            $activeDomains = libvirt_list_domains($this->connection);

            foreach ($activeDomains as $domainId) {
                $domain = libvirt_domain_lookup_by_name($this->connection, $domainId);
                if ($domain) {
                    $info = libvirt_domain_get_info($domain);
                    $xml = libvirt_domain_get_xml_desc($domain, 0);

                    // Einfache IP-Adressextraktion
                    $ip = '';
                    if ($xml) {
                        preg_match('/<ip address=\'([^\']+)\'/', $xml, $matches);
                        $ip = $matches[1] ?? '';
                    }

                    $domains[$domainId] = [
                        'state.state' => (string)($info['state'] ?? 0),
                        'balloon.current' => (string)($info['memory'] ?? 0),
                        'vcpu.current' => (string)($info['nrVirtCpu'] ?? 0),
                        'ip' => $ip
                    ];
                }
            }

            return $this->json($domains);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Führt einen Neustart der virtuellen Maschine durch
     * 
     * Der Reboot wird über das ACPI-Signal eingeleitet, was einem "sauberen" Neustart entspricht.
     * Dies ermöglicht dem Betriebssystem, alle Dienste ordnungsgemäß herunterzufahren.
     * 
     * Wichtige Hinweise:
     * - Benötigt ein funktionierendes ACPI in der VM
     * - Das Betriebssystem muss ACPI-Signale verarbeiten können
     * - Bei fehlgeschlagenem Reboot bleibt die VM im aktuellen Zustand
     * 
     * @param string $name Name der virtuellen Maschine
     * @return JsonResponse Status der Reboot-Operation
     * @throws \Exception Bei Verbindungsproblemen oder wenn die Domain nicht gefunden wird
     */
    public function rebootDomain(string $name): JsonResponse
    {
        try {
            $this->connect();
            $domain = libvirt_domain_lookup_by_name($this->connection, $name);

            if (!$domain) {
                return $this->json([
                    'error' => $this->translator->trans('error.libvirt_domain_not_found')
                ], 404);
            }

            $result = libvirt_domain_reboot($domain);

            return $this->json(new VirtualMachineAction(
                success: $result !== false,
                domain: $name,
                action: 'reboot',
                error: $result === false ? libvirt_get_last_error() : null
            ));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Löscht eine virtuelle Maschine mit allen zugehörigen Dateien
     * 
     * Die Flags für libvirt_domain_undefine_flags setzen sich zusammen aus:
     * 
     * NVRAM (8):
     * - Löscht UEFI/NVRAM Dateien
     * - Wichtig für Windows VMs und UEFI-Boot
     * 
     * MANAGED_SAVE (2):
     * - Löscht gespeicherte VM-Zustände
     * - Vergleichbar mit Hibernate-Dateien
     * 
     * SNAPSHOTS_METADATA (1):
     * - Löscht Snapshot-Informationen
     * - Verhindert verwaiste Snapshot-Daten
     * 
     * @param string $name Name der virtuellen Maschine
     * @param Request $request HTTP-Request mit deleteVhd Option
     * @return JsonResponse Status der Löschoperation
     */
    public function deleteDomain(string $name, Request $request): JsonResponse
    {
        try {
            $this->connect();
            $domain = libvirt_domain_lookup_by_name($this->connection, $name);

            if (!$domain) {
                return $this->json([
                    'error' => $this->translator->trans('error.libvirt_domain_not_found')
                ], 404);
            }

            // Prüfe ob VHD-Dateien auch gelöscht werden sollen
            $data = json_decode($request->getContent(), true);
            $deleteVhd = isset($data['deleteVhd']) && $data['deleteVhd'] === true;

            if ($deleteVhd) {
                // Alle Storage Pools durchsuchen
                $pools = libvirt_list_storagepools($this->connection);
                if ($pools === false) {
                    error_log("Fehler beim Auflisten der Storage Pools: " . libvirt_get_last_error());
                } else {
                    foreach ($pools as $poolName) {
                        $pool = libvirt_storagepool_lookup_by_name($this->connection, $poolName);
                        if ($pool) {
                            // Pool aktualisieren um neue/gelöschte Volumes zu erkennen
                            libvirt_storagepool_refresh($pool);
                        }
                    }
                }


                // XML für Disk-Pfade - robusteres Pattern
                $xml = libvirt_domain_get_xml_desc($domain, 0);
                if ($xml) {
                    preg_match_all('/<disk[^>]+device=[\'"]disk[\'"][^>]*>.*?<source\s+file=[\'"]([^\'""]+)[\'"].*?>/s', $xml, $matches);
                    $diskPaths = $matches[1] ?? [];

                    foreach ($diskPaths as $path) {
                        try {
                            // Debug-Logging
                            error_log("Versuche Volume zu löschen: $path");

                            $volume = libvirt_storagevolume_lookup_by_path($this->connection, $path);
                            if ($volume) {
                                if (!libvirt_storagevolume_delete($volume, 0)) {
                                    error_log("Fehler beim Löschen des Volumes: " . libvirt_get_last_error());
                                }
                            } else {
                                error_log("Volume nicht gefunden: " . libvirt_get_last_error());
                            }
                        } catch (\Exception $e) {
                            error_log("Exception beim Volume-Löschen: " . $e->getMessage());
                        }
                    }
                }
            }

            // Zuerst Domain stoppen falls noch aktiv
            $info = libvirt_domain_get_info($domain);
            if ($info['state'] === 1) {
                libvirt_domain_destroy($domain);
            }

            // Domain undefine mit allen Flags (NVRAM + MANAGED_SAVE + SNAPSHOTS_METADATA)
            $result = libvirt_domain_undefine_flags($domain, 11); // 8 + 2 + 1

            return $this->json(new VirtualMachineAction(
                success: $result !== false,
                domain: $name,
                action: $deleteVhd ? 'delete_with_storage' : 'delete',
                error: $result === false ? libvirt_get_last_error() : null
            ));
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Erstellt eine neue virtuelle Maschine
     * 
     * Diese Methode erstellt eine neue VM mit folgenden Schritten:
     * 1. Erstellen einer QCOW2 Image-Datei als virtuelle Festplatte
     * 2. Generieren der VM-Definition im libvirt XML-Format
     * 3. Registrieren der VM beim Hypervisor
     * 4. Automatischer Start der VM
     *
     * Erwartetes Request-Format:
     * {
     *   "name": "vm-name",           // Eindeutiger Name der VM
     *   "memory": 2048,              // RAM in MB
     *   "vcpus": 2,                  // Anzahl virtueller CPUs
     *   "disk_size": 20,             // Festplattengröße in GB
     *   "iso_image": "/path/to.iso", // Pfad zum Boot-Image
     *   "network_bridge": "br0"      // Netzwerk-Bridge / NAT-Netzwerk
     * }
     * 
     * Die erstellte VM enthält:
     * - QCOW2 Festplatte mit virtio Treibern
     * - IDE CD-ROM mit Boot-ISO
     * - VirtIO Netzwerk-Interface
     * - QXL Grafiktreiber für bessere Performance
     * - VNC/Spice Konsole für Remote-Zugriff
     * 
     * @param Request $request HTTP-Request mit VM-Konfiguration
     * @return JsonResponse Status der Erstellungsoperation
     * @throws \Exception Bei Fehlern während der Erstellung
     */
    public function createDomain(Request $request): JsonResponse
    {
        try {
            $this->connect();
            $data = json_decode($request->getContent(), true);

            // Default Storage Pool holen
            $pool = libvirt_storagepool_lookup_by_name($this->connection, 'default');
            if (!$pool) {
                throw new \Exception($this->translator->trans('error.storage_pool_not_found'));
            }

            // Pool XML parsen für den Basis-Pfad
            $poolXml = libvirt_storagepool_get_xml_desc($pool, 0);
            $poolInfo = simplexml_load_string($poolXml);
            $poolPath = (string)$poolInfo->target->path;

            if (!$poolPath || !is_dir($poolPath)) {
                throw new \Exception($this->translator->trans('error.storage_pool_path_invalid'));
            }

            // VHD-Pfad im Pool
            $vhdPath = $poolPath . '/' . $data['name'] . '.qcow2';

            // QCOW2 Image erstellen
            $command = sprintf(
                'qemu-img create -f qcow2 %s %dG',
                escapeshellarg($vhdPath),
                (int)$data['disk_size']
            );
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception($this->translator->trans('error.create_disk_failed'));
            }

            // XML-Template für die VM
            $xml = sprintf(
                '<?xml version="1.0" encoding="UTF-8"?>
            <domain type="kvm">
                <name>%s</name>
                <memory unit="MiB">%d</memory>
                <vcpu>%d</vcpu>
                <os>
                    <type arch="x86_64">hvm</type>
                    <boot dev="hd"/>
                    <boot dev="cdrom"/>
                </os>
                <features>
                    <acpi/>
                    <apic/>
                </features>
                <devices>
                    <disk type="file" device="disk">
                        <driver name="qemu" type="qcow2"/>
                        <source file="%s"/>
                        <target dev="vda" bus="virtio"/>
                    </disk>
                    <disk type="file" device="cdrom">
                        <driver name="qemu" type="raw"/>
                        <source file="%s"/>
                        <target dev="hdc" bus="ide"/>
                        <readonly/>
                    </disk>
                    <interface type="bridge">
                        <source bridge="%s"/>
                        <model type="virtio"/>
                    </interface>
                    <graphics type="spice" port="-1" autoport="yes">
                        <listen type="address" address="0.0.0.0"/>
                    </graphics>
                    <video>
                        <model type="qxl"/>
                    </video>
                </devices>
            </domain>',
                $data['name'],
                (int)$data['memory'],
                (int)$data['vcpus'],
                $vhdPath,
                $data['iso_image'],
                $data['network_bridge']
            );

            // VM definieren
            $domain = libvirt_domain_define_xml($this->connection, $xml);
            if (!$domain) {
                throw new \Exception($this->translator->trans('error.create_vm_failed'));
            }

            // VM direkt starten
            $result = libvirt_domain_create($domain);

            return $this->json(new VirtualMachineAction(
                success: $result !== false,
                domain: $data['name'],
                action: 'create',
                error: $result === false ? libvirt_get_last_error() : null
            ));
        } catch (\Exception $e) {
            // VHD-Datei aufräumen bei Fehler
            if (isset($vhdPath) && file_exists($vhdPath)) {
                unlink($vhdPath);
            }
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Holt detaillierte Informationen einer VM
     * 
     * Liefert alle verfügbaren Informationen inkl:
     * - Grundkonfiguration (CPU, RAM, Disk)
     * - Aktuelle Auslastung
     * - Netzwerkkonfiguration
     * - SPICE/VNC Zugriffsdaten
     * - Storage Informationen
     * 
     * @param string $name Name der virtuellen Maschine
     * @return JsonResponse Detaillierte VM Informationen
     */
    public function getDomainDetails(string $name): JsonResponse
    {
        try {
            $this->connect();
            $domain = libvirt_domain_lookup_by_name($this->connection, $name);

            if (!$domain) {
                return $this->json([
                    'error' => $this->translator->trans('error.libvirt_domain_not_found')
                ], 404);
            }

            // Basis-Informationen
            $info = libvirt_domain_get_info($domain);
            $xml = libvirt_domain_get_xml_desc($domain, 0);
            $xmlObj = simplexml_load_string($xml);

            // Storage Informationen extrahieren
            $disks = [];
            foreach ($xmlObj->devices->disk as $disk) {
                if ((string)$disk['device'] === 'disk') {
                    $disks[] = [
                        'device' => (string)$disk->target['dev'],
                        'driver' => (string)$disk->driver['type'],
                        'path' => (string)$disk->source['file'],
                        'bus' => (string)$disk->target['bus']
                    ];
                }
            }

            // Netzwerk Informationen
            $networks = [];
            foreach ($xmlObj->devices->interface as $interface) {
                $networks[] = [
                    'type' => (string)$interface['type'],
                    'mac' => (string)$interface->mac['address'],
                    'model' => (string)$interface->model['type'],
                    'bridge' => (string)$interface->source['bridge']
                ];
            }

            // SPICE/VNC Informationen
            $graphics = [];
            foreach ($xmlObj->devices->graphics as $graphic) {
                $graphics[] = [
                    'type' => (string)$graphic['type'],
                    'port' => (string)$graphic['port'],
                    'listen' => (string)$graphic['listen'],
                    'passwd' => (string)$graphic['passwd']
                ];
            }

            // Performance Metriken (CPU, RAM, Disk I/O)
            $stats = [
                'cpu_time' => libvirt_domain_get_info($domain)['cpuUsed'] ?? 0,
                'memory_usage' => $info['memory'] ?? 0,
                'max_memory' => $info['maxMem'] ?? 0
            ];

            return $this->json([
                'name' => $name,
                'state' => $info['state'] ?? 0,
                'maxMemory' => $info['maxMem'] ?? 0,
                'memory' => $info['memory'] ?? 0,
                'cpuCount' => $info['nrVirtCpu'] ?? 0,
                'cpuTime' => $info['cpuUsed'] ?? 0,
                'disks' => $disks,
                'networks' => $networks,
                'graphics' => $graphics,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Erstellt oder findet eine WebSocket-Verbindung für SPICE
     * 
     * @param string $name Name der virtuellen Maschine
     * @return JsonResponse WebSocket-Verbindungsdaten
     */
    public function getSpiceConnection(string $name): JsonResponse
    {
        try {
            $this->connect();
            $domain = libvirt_domain_lookup_by_name($this->connection, $name);
    
            if (!$domain) {
                return $this->json([
                    'error' => $this->translator->trans('error.libvirt_domain_not_found')
                ], 404);
            }
    
            // XML parsen für SPICE-Port mit xmllint (genauer als SimpleXML)
            $xml = libvirt_domain_get_xml_desc($domain, 0);
            $tmpFile = tempnam(sys_get_temp_dir(), 'vm_');
            file_put_contents($tmpFile, $xml);
            
            $spicePort = (int)shell_exec("xmllint --xpath 'string(//graphics[@type=\"spice\"]/@port)' " . escapeshellarg($tmpFile));
            unlink($tmpFile);
    
            if (!$spicePort || $spicePort === 0) {
                return $this->json([
                    'error' => $this->translator->trans('error.no_spice_port')
                ], 404);
            }
    
            // WebSocket Port = SPICE Port + 1000 (wie im Shell-Script)
            $wsPort = $spicePort + 1000;
    
            // Prüfen ob WebSocket bereits läuft
            $checkCmd = "ps aux | grep -v grep | grep 'websockify $wsPort'";
            exec($checkCmd, $output, $returnVar);
    
            if ($returnVar !== 0) {
                // WebSocket noch nicht aktiv, starten
                $cmd = sprintf(
                    'nohup websockify %d localhost:%d > /dev/null 2>&1 & echo $!',
                    $wsPort,
                    $spicePort
                );
                exec($cmd, $output);
    
                // Kurz warten und prüfen ob der Prozess läuft
                sleep(1);
                exec($checkCmd, $output, $returnVar);
                if ($returnVar !== 0) {
                    throw new \Exception('Websockify konnte nicht gestartet werden');
                }
            }
    
            return $this->json([
                'spicePort' => $spicePort,
                'wsPort' => $wsPort,
                'host' => 'localhost'
            ]);
    
        } catch (\Exception $e) {
            error_log("SPICE Connection Error: " . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
