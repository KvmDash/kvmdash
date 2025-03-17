# Installing KVM on Debian 12 "Bookworm"
In this tutorial, I will show you how to install KVM on Debian 12. KVM (Kernel-based Virtual Machine) is a virtualization solution for Linux that allows you to run multiple virtual machines on a single computer. It uses the virtualization extensions of Intel (VT-x) or AMD (AMD-V) to provide a fast and efficient environment.

With KVM, you can run different operating systems and applications separately in virtual machines. This provides more flexibility, better resource utilization, and makes your setup more scalable overall. Let's get started! 🚀

# System Requirements
This howto assumes that you're using Debian 12 (Bookworm) with a minimal installation without a graphical interface.

Before installing KVM, you should make sure that your processor supports the necessary virtualization extensions. KVM only runs on Intel or AMD processors that offer VT-x (for Intel) or AMD-V (for AMD).

To verify this, you can run the following shell script. It looks for the appropriate CPU flags (vmx for Intel, svm for AMD). If these are present, it means your processor supports hardware virtualization. Additionally, the script checks whether the necessary kernel modules for KVM are already loaded.

```bash
#!/bin/bash

# Check if the CPU supports virtualization
if grep -E "(vmx|svm)" /proc/cpuinfo > /dev/null; then
    echo "The CPU supports KVM."
else
    echo "The CPU does not support KVM."
    exit 1
fi

# Check if the KVM modules are loaded
if lsmod | grep kvm > /dev/null; then
    echo "The KVM modules are loaded."
else
    echo "The KVM modules are not loaded. Please ensure that you have installed the correct kernel module 
          for your CPU and virtualization environment and try again."
    exit 1
fi

exit 0
```

Save the script in a file with the .sh extension, for example `cpu_kvm_check.sh`. You can then run it to check if your CPU supports KVM.

In addition to the CPU flags, the KVM installation on Debian 12 also requires loading kernel modules. This includes the `kvm.ko` module, which provides the core virtualization infrastructure, as well as a processor-specific module like `kvm-intel.ko` (for Intel) or `kvm-amd.ko` (for AMD). These modules must be available and loaded for you to use KVM. Make sure your Linux kernel configuration includes these modules and that they can be loaded without issues.

Important: The virtualization features must also be enabled in the BIOS/UEFI. If the CPU flags are missing or virtualization is disabled in the BIOS/UEFI, KVM cannot be used.

# Configuring a Network Bridge
A network bridge allows virtual machines to communicate directly with the physical network as if they were standalone machines. This is especially useful if you want your virtual machines to be reachable by other devices on the network or if you want to provide specific network services on them.

To set up a network bridge in Debian, simply follow these steps:

1. Determine your physical interface:<br/>
    Use the command `ip -f inet a s` to find your physical interface. This could be named something like `enp4s0` or `eth0`.

2. Install bridge-utils:<br/>
    Make sure the bridge-utils package is installed, as you'll need it to manage network bridges. Install it with this command:
    `apt-get install bridge-utils`

3. Update the `/etc/network/interfaces` file:<br/>
    Make sure that only the loopback interface (lo) is active in the file. Anything related to your physical interface (e.g., enp4s0) should be removed.
    Example content for the `/etc/network/interfaces` file:
    ```bash
    # This file describes the network interfaces available on your system
    # and how to activate them. For more information, see interfaces(5).
    source /etc/network/interfaces.d/*
    # The loopback network interface
    auto lo
    iface lo inet loopback
    ```

4. Configure the bridge (br0) in the `/etc/network/interfaces.d/br0` file:
    Open or create a file and add the appropriate configuration.
    Example content for the file, depending on whether you want to use DHCP or a static IP:
    ## DHCP Configuration:
    ```bash
    # DHCP configuration file for br0 ##
    auto br0
    # Bridge configuration
    iface br0 inet dhcp
    bridge_ports enp4s0
    ```

    ## Static IP Configuration:
    ```bash
    ## Static IP configuration file for br0 ##
    auto br0
    
    # Bridge configuration
    iface br0 inet static
        address 192.168.0.200
        broadcast 192.168.0.255
        netmask 255.255.255.0
        gateway 192.168.0.1
        # If the resolvconf package is installed, you should not
        # manually edit the resolv.conf configuration file. Define nameservers here.
        # dns-nameservers 192.168.2.254
        # If you have multiple interfaces like eth0 and eth1
        # bridge_ports eth0 eth1
        bridge_ports enp4s0
        bridge_stp off       # Disable Spanning Tree Protocol
        bridge_waitport 0    # No delay until a port becomes available
        bridge_fd 0          # No forwarding delay
    ```
# Restarting the Network Service in Linux:

Before restarting the network service, you should make sure that the firewall is disabled. This is because the firewall rule might still refer to the old interface (e.g., `enp4s0`). After restarting, you'll need to adjust the firewall rule for the new interface (br0).

1. Use the following command to restart the network service:<br/>
`systemctl restart networking`

2. Then check if the service was restarted successfully:<br/>
`systemctl status networking`

# Installation

Once you've confirmed that your system supports virtualization, you can install KVM on Debian 12. Simply run the following command:

```bash
apt install qemu-kvm qemu-utils libvirt-daemon-system virtinst bridge-utils
```
* qemu-kvm:<br/>
    QEMU is an open-source virtualizer that allows you to run virtual machines on your host system. The qemu-system package contains the main components of QEMU that you need to create and run VMs.

* qemu-utils:<br/>
    This package contains useful utilities and tools associated with QEMU. These include functions like converting disk images or creating snapshots.

* libvirt-daemon-system:<br/>
    Libvirt is a toolkit and API for managing virtual machines and other virtualization resources. The libvirt-daemon-system package contains the libvirt daemon, which is the main component of libvirt and enables VM management.

* virtinst:<br/>
    The virtinst package is a command-line tool for creating and managing virtual machines on systems using libvirt. It simplifies the creation of new VMs through a simple command interface.

* bridge-utils:<br/>
Is a package that provides tools for managing network bridges.

By installing these packages, you get a complete virtualization environment on your Debian 12 system that allows you to create, manage, and run VMs.

Next, we'll cover the basics of Libvirt:
[Introduction to libvirt](https://github.com/KvmDash/kvmdash/blob/main/docs/libvirt-Debian.md)
