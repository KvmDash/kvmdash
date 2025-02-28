<?php

/**
 * Connects to the specified libvirt hypervisor
 * @param string $url Connection URL
 * @param bool $readonly Whether the connection is read-only
 * @return resource|false Connection resource or FALSE on error
 */
function libvirt_connect(string $url, bool $readonly = false) {}

/**
 * Gets the last error message
 * @return string Error message
 */
function libvirt_get_last_error() {}

/**
 * Lists active domains
 * @param resource $connection Libvirt connection resource
 * @return array Array of domain names
 */
function libvirt_list_domains($connection) {}

/**
 * Lists inactive domains
 * @param resource $connection Libvirt connection resource
 * @return array Array of domain names
 */
function libvirt_list_defined_domains($connection) {}

/**
 * Looks up domain by name
 * @param resource $connection Libvirt connection resource
 * @param string $name Domain name
 * @return resource|false Domain resource or FALSE on error
 */
function libvirt_domain_lookup_by_name($connection, string $name) {}

/**
 * Looks up domain by ID
 * @param resource $connection Libvirt connection resource
 * @param int $id Domain ID
 * @return resource|false Domain resource or FALSE on error
 */
function libvirt_domain_lookup_by_id($connection, int $id) {}

/**
 * Gets domain information
 * @param resource $domain Domain resource
 * @return array Domain information array
 */
function libvirt_domain_get_info($domain) {}

/**
 * Gets domain name
 * @param resource $domain Domain resource
 * @return string Domain name
 */
function libvirt_domain_get_name($domain) {}

/**
 * Start a domain
 * @param resource $domain Domain resource
 * @return bool TRUE on success, FALSE on failure
 */
function libvirt_domain_create($domain) {}

/**
 * Shutdown a domain
 * @param resource $domain Domain resource
 * @return bool TRUE on success, FALSE on failure
 */
function libvirt_domain_shutdown($domain) {}

/**
 * Force stop a domain
 * @param resource $domain Domain resource
 * @return bool TRUE on success, FALSE on failure
 */
function libvirt_domain_destroy($domain) {}

/**
 * Get the version of libvirt
 * @return array|false Version information array or FALSE on error
 */
function libvirt_version() {}