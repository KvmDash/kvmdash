# 1. Introduction to Libvirt

**Libvirt** is an open-source library and toolset for managing virtual machines and resources such as storage and networks. It provides a unified interface for various virtualization solutions on Linux and simplifies the automation of virtualization tasks. It's ideal for admins and developers who want to manage VMs efficiently.

**Why Libvirt?**

* Supports various virtualization technologies such as KVM, QEMU, and Xen.
* Provides a unified API for different virtualization tools.
* Enables simple management of VMs, networks, and storage via CLI or GUI.
* Widely used, with an active community and regular updates.

# 2. Installation of Libvirt

Open a terminal and install Libvirt with:

```bash
apt update
apt install libvirt-daemon-system libvirt-clients qemu-kvm
```

After installation, the Libvirt service normally starts automatically. You can check this with the following command:<br/>
`systemctl status libvirtd`

If the service isn't running, you can start it with:<br/>
`systemctl start libvirtd`

# 3. Configuration of Libvirt

**Understanding configuration files**:<br/>
Libvirt uses various configuration files to store its settings. The most important files are:
* **/etc/libvirt/libvirtd.conf:**<br/>
This file contains the general configuration settings for the Libvirt daemon.
* **/etc/libvirt/qemu.conf:**<br/> Here, specific settings for the QEMU hypervisor backend configuration are stored.
* **/etc/libvirt/qemu/*.xml:**<br/> These XML files contain the configurations for each virtual machine.

**Network configuration:**<br/> 
Libvirt offers various network options for virtual machines, including NAT, Bridged Networking, and Host-Only Networking. Configuration typically occurs via the virsh tool or the graphical user interface virt-manager.

**Configuring Libvirt for the bridge:**<br/>
1. Open a terminal session on your Linux system.

2. Create an XML file for the network configuration. 
   You can use a text editor of your choice. For example:<br/> `nano /etc/libvirt/qemu/networks/bridge.xml`
3. Add the following content to the file:<br/>
    ```xml
    <network>
      <name>br0</name>
      <forward mode="bridge"/>
      <bridge name="br0"/>
    </network>
    ```
4. Save the file and close the text editor.
5. Define the network with the command:<br/>
    `virsh net-define /etc/libvirt/qemu/networks/bridge.xml`
6. Enable the network to start automatically at system boot:<br/>
    `virsh net-autostart br0`
7. Start the network:<br/>
    `virsh net-start br0`
8. Verify that the Libvirt network is correctly defined and started:
    `virsh net-list --all`

With this, the bridge is successfully configured for use by Libvirt. You can now create virtual machines and configure them to use the br0 network.

# 4. Managing Virtual Machines with Libvirt
Libvirt offers various ways to manage VMs. Here we'll look at the basic commands for creating, starting, stopping, and restarting VMs.

**Creating a virtual machine**
You can either use the command-line interface (virsh) or a graphical interface like virt-manager. In this tutorial, we focus on the CLI.

Here's an example of creating a VM with Libvirt:<br/>
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

The above command creates a VM named "mydebian" with 4 GB RAM, 4 vCPUs, a 20 GB virtual hard disk, an ISO image for installation, and a network connection via the br0 bridge.

**⚠ Important:** Replace `/root/iso/debian-12.5.0-amd64-netinst.iso` with the actual path to your ISO image!

While the VM starts, you can follow the installation in the terminal and follow instructions for configuration. After completing the installation, the VM should connect to the network via br0 and be ready for use. 🚀

## Basics of Virtual Machines with Libvirt

After creating a virtual machine, you may find that you need to start, stop, or restart it. Here are the basic steps to perform these actions with Libvirt:

### Starting a virtual machine:<br/>
To start a virtual machine, use the command<br/>
`virsh start <name_of_the_virtual_machine>`. 

For example:<br/>
`virsh start mydebian`

### Stopping a virtual machine:<br/>
To stop a running virtual machine, use the command<br/>
`virsh shutdown <name_of_the_virtual_machine>`. 

For example:<br/>
`virsh shutdown mydebian`

### Restarting a virtual machine:<br/>
To restart a virtual machine, use the command<br/>
`virsh reboot <name_of_the_virtual_machine>`. 

For example:<br/>
`virsh reboot mydebian`

 ### Creating a snapshot:<br/>
 To create a snapshot of a virtual machine, use the command<br/>
  `virsh snapshot-create-as <name_of_the_virtual_machine> <snapshot_name>`. 
 
 For example:<br/>
` virsh snapshot-create-as mydebian snapshot1`

### Restoring a snapshot: <br/>
To restore a virtual machine from a snapshot, use the command<br/>
`virsh snapshot-revert <name_of_the_virtual_machine> <snapshot_name>`. 

For example:<br/>
`virsh snapshot-revert mydebian snapshot1`

⚠ Please note that while restoring a virtual machine from a snapshot, all changes since the time of the snapshot will be lost.

# 5. Using virt-manager<br/>
virt-manager is a graphical user interface (GUI) that allows you to manage and monitor virtual machines. With virt-manager, you can create, start, stop, monitor, and do much more with virtual machines, all through a user-friendly interface.

To use virt-manager, install it on your system with:<br/>
`apt install virt-manager`

Then start virt-manager with: `virt-manager`
