<?php
require_once __DIR__ . '/../../../../init.php';

use WHMCS\Database\Capsule;

$moduleConfig = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'scantra_phishing_detection')
            ->pluck('value', 'setting');
        $apiToken = $moduleConfig['API Token'] ?? '';
        $enableDomainTracking = $moduleConfig['Enable Domain Tracking'] ?? '';  
        $autoSuspendDomains = $moduleConfig['Auto Suspend Domains'] ?? '';
        $createSupportTickets = $moduleConfig['Create Support Tickets'] ?? '';

/*
   $apiToken = $row['apiToken'];

        $postData = [
            'signature' => hash_hmac('sha256', $apiToken),
            'incidentId' => $incidentId,
            'domain' => $domain,
            'url' => $url,
            'screenshotUrl' => $screenshotUrl,
            'targetCompany' => $targetCompany,
            'registrar' => $registrar,
            'hostingProvider' => $hostingProvider,
            'description' => $description,
            'domainRegistrationDate' => $domainRegistrationDate,
            'type' => $type,
            'steps' => $stepsArray
        ];
        $ch = curl_init($callbackUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Log callback
        $sql = "INSERT INTO callbacklogs (providerId, payload, createdAt, response) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $payload = json_encode($postData);
        $responseData = "HTTP Code: $httpCode, Response: $response";
        $stmt->bind_param("isss", $providerId, $payload, $createdAt, $responseData);
        $stmt->execute();
    }
        */
// Validate API Token
if (empty($apiToken)) {
    die('API Token is not configured.');
}
// Process incoming POST request
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die('Invalid JSON payload.');
}
$requiredFields = ['incidentId', 'domain', 'url', 'screenshotUrl', 'targetCompany', 'registrar', 'hostingProvider', 'description', 'domainRegistrationDate', 'type', 'steps', 'signature'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        die("Missing required field: $field");
    }
}
// Verify signature
$payload = json_encode(array_diff_key($data, ['signature' => true]));
$computedSignature = hash_hmac('sha256', $payload, $apiToken);
if (!hash_equals($computedSignature, $data['signature'])) {
    http_response_code(403);
    die('Invalid signature.');
}
// If automatic domain suspension is enabled, suspend the domain
if ($autoSuspendDomains === 'on') {
    $domain = $data['domain'];
    Capsule::table('tbldomains')
        ->where('domain', $domain)
        ->update(['status' => 'Suspended']);
}
// If support ticket creation is enabled, create a ticket
if ($createSupportTickets === 'on') {
    $ticketSubject = "Phishing Incident Detected: " . $data['domain'];
    $ticketMessage = "A phishing incident has been detected with the following details:\n\n";
    $ticketMessage .= "Incident ID: " . $data['incidentId'] . "\n";
    $ticketMessage .= "Domain: " . $data['domain'] . "\n";
    $ticketMessage .= "URL: " . $data['url'] . "\n";
    $ticketMessage .= "Target Company: " . $data['targetCompany'] . "\n";
    $ticketMessage .= "Registrar: " . $data['registrar'] . "\n";
    $ticketMessage .= "Hosting Provider: " . $data['hostingProvider'] . "\n";
    $ticketMessage .= "Description: " . $data['description'] . "\n";
    $ticketMessage .= "Domain Registration Date: " . $data['domainRegistrationDate'] . "\n";
    $ticketMessage .= "Type: " . $data['type'] . "\n";
    $ticketMessage .= "Steps: \n";
    foreach ($data['steps'] as $step) {
        $ticketMessage .= "- " . $step . "\n";
    }
    $ticketData = [
        'deptid' => 1,
        'subject' => $ticketSubject,
        'message' => $ticketMessage,
        'name' => 'Phishing Detection System',
        'email' => 'noreply@scantra.org',
        'priority' => 'High',
        'status' => 'Open',
    ];
    $result = localAPI('OpenTicket', $ticketData);
    if ($result['result'] !== 'success') {
        error_log('Failed to create support ticket: ' . json_encode($result));
    }
}

// Log the incident
error_log('Phishing incident processed: ' . $data['incidentId'] . ' for domain: ' . $data['domain']);

http_response_code(200);
echo 'Incident processed successfully.';
exit;