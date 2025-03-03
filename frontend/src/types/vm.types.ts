/**
 * Eine virtuelle Maschine, wie sie von der API zurückgegeben wird
 */
export interface VMResponse {
    name: string;    // Name der VM (z.B. "ubuntu-server")
    state: number;   // Status der VM (1 = running, 3 = paused, 5 = shutdown)
}

/**
 * Formular-Daten für das Erstellen einer neuen VM
 */
export interface VmFormData {
    name: string;           // Name der VM
    memory: number;         // RAM in MB
    vcpus: number;         // Anzahl virtueller CPUs
    disk_size: number;     // Festplattengröße in GB
    iso_image: string;     // Pfad zum ISO-Image
    network_bridge: string; // Name der Netzwerkbrücke
    os_variant: string;    // Betriebssystem-Variante
}



/**
 * SPICE Verbindungskonfiguration
 */
export interface VmSpiceConfig {
    port: string;    // Port für die Verbindung
    type: string;    // Verbindungstyp
    listen: string;  // Listen-Adresse
}


/**
 * Betriebssystem-Informationen
 */
export interface VmOsInfo {
    type: string;    // Betriebssystemtyp
    arch: string;    // CPU-Architektur
}

/**
 * Netzwerk-Interface einer VM
 */
export interface VmNetworkInterface {
    name: string;            // Name des Interfaces
    hardware_address: string; // MAC-Adresse
    ip_addresses: Array<{    // IP-Adressen
        type: string;        // IP-Version (IPv4/IPv6)
        address: string;     // IP-Adresse
    }>;
}

/**
 * Detaillierte VM-Statistiken
 */
export interface VmStats {
    cpu: {
        total_time: number;    // Gesamte CPU-Zeit
        user_time: number;     // CPU-Zeit im User-Mode
        system_time: number;   // CPU-Zeit im System-Mode
    };
    memory: {
        current: number;       // Aktuell genutzter RAM
        available: number;     // Verfügbarer RAM
        unused: number;        // Ungenutzter RAM
        rss: number;          // Resident Set Size
    };
    disk: {
        [key: string]: {      // Pro Festplatte
            reads: number;     // Lesevorgänge
            writes: number;    // Schreibvorgänge
            capacity: number;  // Gesamtkapazität
            allocation: number; // Belegte Kapazität
        };
    };
    network: {
        [key: string]: {      // Pro Netzwerk-Interface
            rx_bytes: number;  // Empfangene Bytes
            tx_bytes: number;  // Gesendete Bytes
            rx_packets: number; // Empfangene Pakete
            tx_packets: number; // Gesendete Pakete
        };
    };
}

/**
 * Detaillierte VM-Informationen
 */
export interface VmDetails {
    name: string;               // Name der VM
    memory: string;            // Zugewiesener RAM
    vcpu: string;             // Anzahl virtueller CPUs
    os: VmOsInfo;             // Betriebssystem-Info
    spice: VmSpiceConfig;     // SPICE-Konfiguration
    network: VmNetworkInterface[]; // Netzwerk-Interfaces
    stats: VmStats;           // Aktuelle Statistiken
}


/**
 * Status einer VM vom status endpoint
 */
export interface VmStatus {
    'state.state': string;      // Status der VM (1 = running, 5 = shutdown)
    'balloon.current': string;  // Aktueller RAM in KB
    'vcpu.current': string;    // Anzahl aktiver vCPUs
    'ip': string;              // IP-Adresse der VM
}

/**
 * Response vom /virt/domains/status endpoint
 */
export interface VmStatusResponse {
    [key: string]: VmStatus;  // Key ist der VM-Name
}