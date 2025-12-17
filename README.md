<div align="center">
  <img src="modules/addons/logo.png" alt="Scantra Logo" width="300">
</div>

# Scantra Phishing Detection Module for WHMCS

A comprehensive WHMCS addon module that integrates with Scantra's API to provide automated phishing detection and domain management for hosting providers.

## Overview

The Scantra Phishing Detection module automatically monitors your customers' domains and hosting services for phishing activities. It integrates seamlessly with WHMCS to track domain registrations, renewals, suspensions, and terminations while communicating with Scantra's API for real-time phishing detection.

## Features

- **Automatic Domain Tracking**: Automatically adds domains to Scantra's monitoring system when registered or created
- **Phishing Incident Detection**: Receives real-time webhooks when phishing incidents are detected
- **Auto-Suspension**: Optionally suspend domains automatically when phishing is detected
- **Support Ticket Creation**: Automatically creates support tickets for detected phishing incidents
- **Lifecycle Management**: Tracks domain lifecycle events (registration, renewal, suspension, termination, expiration)
- **Secure API Integration**: Uses bearer token authentication for secure communication with Scantra API

## Requirements

- WHMCS 7.0 or higher
- PHP 7.2 or higher
- cURL extension enabled
- Valid Scantra API token

## Installation

### Step 1: Download and Extract

1. Download the module files
2. Extract the `scantra_phishing_detection` folder

### Step 2: Upload to WHMCS

Upload the entire `scantra_phishing_detection` folder to your WHMCS installation:

```
/path/to/whmcs/modules/addons/scantra_phishing_detection/
```

Your directory structure should look like this:

```
modules/
└── addons/
    └── scantra_phishing_detection/
        ├── hooks.php
        ├── scantra_phishing_detection.php
        ├── callback/
        │   └── add_incident.php
        ├── lang/
        │   └── english.php
        └── lib/
            ├── Admin/
            │   ├── AdminDispatcher.php
            │   └── Controller.php
            └── Client/
                ├── ClientDispatcher.php
                └── Controller.php
```

### Step 3: Activate the Module

1. Log in to your WHMCS Admin area
2. Navigate to **Setup > Addon Modules**
3. Find "Scantra Phishing Detection" in the list
4. Click **Activate**

### Step 4: Configure the Module

After activation, configure the following settings:

1. Click **Configure** next to the Scantra Phishing Detection module
2. Set the following configuration options:

| Setting | Description |
|---------|-------------|
| **API Token** | Your Scantra API token (required) - obtain this from your Scantra account |
| **Enable Domain Tracking** | Enable automatic domain tracking with Scantra API |
| **Auto Suspend Domains** | Automatically suspend domains when phishing incidents are detected |
| **Create Support Tickets** | Automatically create support tickets for phishing incidents |

3. Click **Save Changes**

### Step 5: Configure Webhook Callback

To receive phishing incident notifications from Scantra:

1. Configure your Scantra account to send webhooks to:
   ```
   https://your-whmcs-domain.com/modules/addons/scantra_phishing_detection/callback/add_incident.php
   ```

2. Ensure the URL is accessible from the internet (not behind authentication)

## Usage

### Automatic Domain Tracking

Once configured, the module automatically tracks domains through their lifecycle:

#### Domain Registration
When a domain is registered through WHMCS:
- Domain is automatically added to Scantra monitoring
- API call is made to `https://providers-api.scantra.org/v1/add_domain.php`

#### Hosting Service Creation
When a hosting service is created:
- Service domain is automatically added to Scantra monitoring
- Works with cPanel, Plesk, and other hosting modules

#### Domain Suspension
When a domain or service is suspended:
- If "Auto Suspend Domains" is enabled, domain is removed from Scantra monitoring
- API call is made to `https://providers-api.scantra.org/v1/delete_domain.php`

#### Domain Renewal
When a domain is renewed:
- Domain is re-added to Scantra monitoring
- Ensures continued protection after renewal

#### Domain Termination
When a domain or service is terminated:
- Domain is removed from Scantra monitoring
- Prevents unnecessary monitoring of inactive domains

#### Domain Expiration
Daily cron job checks for expiring domains:
- Domains expiring within 1 day are automatically removed from monitoring (if auto-suspend is enabled)
- Prevents monitoring of expired domains

### Phishing Incident Handling

When Scantra detects a phishing incident on a monitored domain:

1. **Webhook Received**: Scantra sends incident details to your callback URL
2. **Signature Verification**: HMAC signature is verified for security
3. **Automatic Actions** (based on configuration):
   - **Domain Suspension**: Domain status is updated to "Suspended" in WHMCS
   - **Ticket Creation**: A support ticket is created with full incident details

#### Incident Data Received

The callback receives the following information:
- Incident ID
- Domain name
- Phishing URL
- Screenshot URL
- Target company being impersonated
- Domain registrar
- Hosting provider
- Incident description
- Domain registration date
- Incident type
- Remediation steps

#### Support Ticket Content

When "Create Support Tickets" is enabled, tickets include:
- Incident ID
- Domain details
- Target company
- Registrar and hosting provider information
- Description of the phishing incident
- Recommended remediation steps

## API Integration

### Scantra API Endpoints

The module communicates with the following Scantra API endpoints:

#### Add Domain
```
POST https://providers-api.scantra.org/v1/add_domain.php
Authorization: Bearer {API_TOKEN}
Content-Type: application/x-www-form-urlencoded

domain={domain_name}
```

#### Delete Domain
```
POST https://providers-api.scantra.org/v1/delete_domain.php
Authorization: Bearer {API_TOKEN}
Content-Type: application/x-www-form-urlencoded

domain={domain_name}
```

### Webhook Callback Format

Scantra sends phishing incidents to your callback URL with the following JSON payload:

```json
{
  "incidentId": "123456",
  "domain": "example.com",
  "url": "https://phishing-example.com/login",
  "screenshotUrl": "https://scantra.org/screenshots/123456.png",
  "targetCompany": "PayPal",
  "registrar": "Namecheap",
  "hostingProvider": "Your Hosting Company",
  "description": "Phishing page impersonating PayPal login",
  "domainRegistrationDate": "2024-01-15",
  "type": "credential_theft",
  "steps": [
    "Suspend the domain immediately",
    "Contact the domain owner",
    "Report to authorities if necessary"
  ],
  "signature": "computed_hmac_signature"
}
```

## Security

### Signature Verification

All incoming webhooks are verified using HMAC-SHA256 signatures to ensure authenticity:

1. The payload (excluding signature) is JSON-encoded
2. HMAC-SHA256 hash is computed using your API token as the secret key
3. The computed signature is compared with the received signature
4. Requests with invalid signatures are rejected with HTTP 403

### Best Practices

- Keep your API token secure and never commit it to version control
- Use HTTPS for your WHMCS installation to protect data in transit
- Regularly review activity logs for suspicious API calls
- Monitor support tickets created by the module

## Troubleshooting

### Module Not Appearing

**Problem**: Module doesn't appear in Addon Modules list

**Solution**:
- Verify files are in correct directory: `/modules/addons/scantra_phishing_detection/`
- Check file permissions (should be readable by web server)
- Clear WHMCS cache: **Utilities > System > Clear Cache**

### Domains Not Being Tracked

**Problem**: Domains aren't being added to Scantra

**Solution**:
1. Verify "Enable Domain Tracking" is set to "Yes"
2. Ensure API token is correctly configured
3. Check WHMCS Activity Log for error messages:
   - Navigate to **Utilities > Logs > Module Log**
   - Look for "Scantra Phishing Detection" entries
4. Verify API token is valid by testing manually with cURL:
   ```bash
   curl -X POST https://providers-api.scantra.org/v1/add_domain.php \
     -H "Authorization: Bearer YOUR_API_TOKEN" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "domain=test.com"
   ```

### Webhooks Not Working

**Problem**: Phishing incidents not triggering actions in WHMCS

**Solution**:
1. Verify callback URL is publicly accessible
2. Check that the URL doesn't require authentication
3. Review server error logs for PHP errors
4. Test the callback manually:
   ```bash
   curl -X POST https://your-whmcs.com/modules/addons/scantra_phishing_detection/callback/add_incident.php \
     -H "Content-Type: application/json" \
     -d '{
       "incidentId": "test123",
       "domain": "test.com",
       "url": "https://test.com/phish",
       "screenshotUrl": "https://example.com/screenshot.png",
       "targetCompany": "Test Company",
       "registrar": "Test Registrar",
       "hostingProvider": "Test Host",
       "description": "Test incident",
       "domainRegistrationDate": "2024-01-01",
       "type": "test",
       "steps": ["Step 1"],
       "signature": "COMPUTE_VALID_SIGNATURE"
     }'
   ```

### Support Tickets Not Created

**Problem**: Tickets aren't being created for incidents

**Solution**:
1. Verify "Create Support Tickets" is enabled
2. Check that department ID is valid (default is 1)
3. Review WHMCS Activity Log for errors
4. Ensure WHMCS ticket system is properly configured

## Activity Logging

The module logs all important events to the WHMCS Activity Log:

- Successful domain additions
- Domain suspensions and deletions
- HTTP errors from Scantra API
- General module errors

To view logs:
1. Navigate to **Utilities > Logs > Activity Log**
2. Search for "Scantra Phishing Detection"

## Hooks Reference

The module registers the following WHMCS hooks:

| Hook | Trigger | Action |
|------|---------|--------|
| `AfterRegistrarRegister` | Domain registered | Add domain to Scantra |
| `AfterModuleCreate` | Hosting service created | Add domain to Scantra |
| `AfterModuleSuspend` | Service suspended | Remove domain from Scantra (if auto-suspend enabled) |
| `AfterModuleUnsuspend` | Service unsuspended | Re-add domain to Scantra |
| `AfterModuleTerminate` | Service terminated | Remove domain from Scantra |
| `DailyCronJob` | Daily cron execution | Remove expiring domains (1 day before expiry) |
| `AfterRegistrarRenewal` | Domain renewed | Re-add domain to Scantra |

## Support

For support with this module:

- **Scantra Support**: Contact Scantra for API-related issues
- **Module Issues**: Check WHMCS logs and documentation
- **WHMCS Integration**: Refer to WHMCS developer documentation at https://developers.whmcs.com/

## License

This module is provided by Scantra for use with WHMCS installations.

## Changelog

### Version 1.0
- Initial release
- Automatic domain tracking
- Phishing incident webhooks
- Auto-suspension capability
- Support ticket creation
- Domain lifecycle management

## Credits

**Author**: Scantra  
**Version**: 1.0  
**WHMCS Compatibility**: 7.0+
