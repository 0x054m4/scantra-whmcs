<?php
/**
 * WHMCS SDK Sample Addon Module Hooks File
 *
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * This allows you to execute your own code in addition to, or sometimes even
 * instead of that which WHMCS executes by default.
 *
 * @see https://developers.whmcs.com/hooks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Register a hook with WHMCS.
 *
 * This sample demonstrates triggering a service call when a change is made to
 * a client profile within WHMCS.
 *
 * For more information, please refer to https://developers.whmcs.com/hooks/
 *
 * add_hook(string $hookPointName, int $priority, string|array|Closure $function)
 */
add_hook('AfterRegistrarRegister', 1, function($vars) {
    try {
        // Get module configuration settings
        $moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
        
        // Only proceed if domain tracking is enabled and API token is set
        if (!$enableDomainTracking || !$apiToken) {
            
            return;
        }
        
        $domainid = $vars['domainid'];
        $domain = \WHMCS\Domain\Domain::find($domainid);
        $domainName = $domain->domain;

        if($domainName) {
            if($enableDomainTracking) {

                // Call Scantra API to add domain for phishing detection
                $ch = curl_init('https://providers-api.scantra.org/v1/add_domain.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                // Requires header bearer token for authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                // www-form-urlencoded data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'domain' => $domainName
                ]));    
                $response = curl_exec($ch);

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    logActivity($response);
                    }
                } else {
                    logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
            }
        }
    } catch (Exception $e) {
        // Consider logging or reporting the error.
        logActivity('Scantra Phishing Detection Error: ' . $e->getMessage());
    }
});

// Track new hosting services
add_hook('AfterModuleCreate', 1, function($vars) {
    try {
        // Get module configuration settings
        $moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
        // Only proceed if domain tracking is enabled and API token is set
        if (!$enableDomainTracking || !$apiToken) {
            return;
        }
        $serviceid = $vars['serviceid'];
        $service = \WHMCS\Service\Service::find($serviceid);
        $domainName = $service->domain;
        if($domainName) {
            if($enableDomainTracking) {
                // Call Scantra API to add domain for phishing detection
                $ch = curl_init('https://providers-api.scantra.org/v1/add_domain.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                // Requires header bearer token for authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                // www-form-urlencoded data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'domain' => $domainName
                ]));    
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    logActivity('Scantra Phishing Detection: Domain ' . $domainName . ' added  successfully.');
                } else {
                    logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
                }
            }
        }
    } catch (Exception $e) {
        // Consider logging or reporting the error.
        logActivity('Scantra Phishing Detection Error: ' . $e->getMessage());
    }
});
// Track domain suspensions
add_hook('AfterModuleSuspend', 1, function($vars) {
    try {
        // Get module configuration settings
        $moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
        // Only proceed if domain tracking is enabled and API token is set
        if (!$enableDomainTracking || !$apiToken || !$autoSuspendDomains) {
            return;
        }
        $serviceid = $vars['serviceid'];
        $service = \WHMCS\Service\Service::find($serviceid);
        $domainName = $service->domain;
        if($domainName) {
            if($enableDomainTracking && $autoSuspendDomains) {
                // Call Scantra API to suspend domain for phishing detection
                $ch = curl_init('https://providers-api.scantra.org/v1/delete_domain.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                // Requires header bearer token for authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                // www-form-urlencoded data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'domain' => $domainName
                ]));    
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    logActivity('Scantra Phishing Detection: Domain ' . $domainName . ' suspended successfully.');
                } else {
                    logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
                }
            }
        }
    } catch (Exception $e) {
        // Consider logging or reporting the error.
        logActivity('Scantra Phishing Detection Error: ' . $e->getMessage());
    }
});

// Track domain unsuspensions
add_hook('AfterModuleUnsuspend', 1, function($vars) {
    try {
        // Get module configuration settings
        $moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
        // Only proceed if domain tracking is enabled and API token is set
        if (!$enableDomainTracking || !$apiToken) {
            return;
        }
        $serviceid = $vars['serviceid'];
        $service = \WHMCS\Service\Service::find($serviceid);
        $domainName = $service->domain;
        if($domainName) {
            if($enableDomainTracking) {
                // Call Scantra API to add domain for phishing detection
                $ch = curl_init('https://providers-api.scantra.org/v1/add_domain.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                // Requires header bearer token for authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                // www-form-urlencoded data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'domain' => $domainName
                ]));    
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    logActivity('Scantra Phishing Detection: Domain ' . $domainName . ' added successfully.');
                    }
                } else {
                    logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
            }
        }
    } catch (Exception $e) {
        // Consider logging or reporting the error.
        logActivity('Scantra Phishing Detection Error: ' . $e->getMessage());
    }
});

// Track domain terminations
add_hook('AfterModuleTerminate', 1, function($vars) {
    try {
        // Get module configuration settings
        $moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
        // Only proceed if domain tracking is enabled and API token is set
        if (!$enableDomainTracking || !$apiToken) {
            return;
        }
        $serviceid = $vars['serviceid'];
        $service = \WHMCS\Service\Service::find($serviceid);
        $domainName = $service->domain;
        if($domainName) {
            if($enableDomainTracking) {
                // Call Scantra API to delete domain for phishing detection
                $ch = curl_init('https://providers-api.scantra.org/v1/delete_domain.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                // Requires header bearer token for authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                // www-form-urlencoded data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'domain' => $domainName
                ]));    
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    logActivity('Scantra Phishing Detection: Domain ' . $domainName . ' deleted successfully.');
                } else {
                    logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
                }
            }
        }
    } catch (Exception $e) {
        // Consider logging or reporting the error.
        logActivity('Scantra Phishing Detection Error: ' . $e->getMessage());
    }
});

// Track registrar expirations
add_hook('DailyCronJob', 1, function($vars) {
    
    $expiryThresholds = [1]; // Days before expiry
    $capsule = \WHMCS\Database\Capsule::getInstance();
    $moduleConfig = $capsule->table('tbladdonmodules')
        ->where('module', 'scantra_phishing_detection')
        ->pluck('value', 'setting');
    $apiToken = $moduleConfig['API Token'] ?? '';
    $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
    $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
    $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
    // Only proceed if domain tracking is enabled and API token is set
    if (!$enableDomainTracking || !$apiToken || !$autoSuspendDomains) {
        return;
    }

    
    foreach ($expiryThresholds as $days) {
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $domains = Capsule::table('tbldomains')
            ->where('status', 'Active')
            ->whereDate('expirydate', '=', $targetDate)
            ->get();
        
        foreach ($domains as $domain) {
            $domainName = $domain->domain;
            if($domainName) {
                if($enableDomainTracking && $autoSuspendDomains) {
                    // Call Scantra API to suspend domain for phishing detection
                    $ch = curl_init('https://providers-api.scantra.org/v1/delete_domain.php');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    // Requires header bearer token for authentication
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $apiToken,
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    // www-form-urlencoded data
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'domain' => $domainName
                    ]));    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($httpCode === 200) {
                        logActivity('Scantra Phishing Detection: Domain ' . $domainName . ' suspended due to impending expiry.');
                    } else {
                        logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
                    }
                }
            }

        }
    }
});

// Domains that've been renewed should be re-added to tracking
add_hook('AfterRegistrarRenewal', 1, function($vars) {
    try {
        // Get module configuration settings
        $moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';
        // Only proceed if domain tracking is enabled and API token is set
        if (!$enableDomainTracking || !$apiToken) {
            return;
        }
        $domainid = $vars['domainid'];
        $domain = \WHMCS\Domain\Domain::find($domainid);
        $domainName = $domain->domain;  
        if($domainName) {
            if($enableDomainTracking) {
                // Call Scantra API to add domain for phishing detection
                $ch = curl_init('https://providers-api.scantra.org/v1/add_domain.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                // Requires header bearer token for authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                // www-form-urlencoded data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'domain' => $domainName
                ]));    
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    logActivity('Scantra Phishing Detection: Domain ' . $domainName . ' re-added successfully after renewal.');
                } else {
                    logActivity('Scantra Phishing Detection HTTP Error: Received HTTP code ' . $httpCode);
                }
            }
        }
    } catch (Exception $e) {
        // Consider logging or reporting the error.
        logActivity('Scantra Phishing Detection Error: ' . $e->getMessage());
    }
});
