# KIPMI Wallet Authentication Plugin

A Moodle authentication plugin that enables users to log in using KIPMI wallet and Verifiable Credentials (OIDC4VP).

## Description

This plugin allows users to authenticate to Moodle by scanning a QR code with their KIPMI wallet application.

## Requirements

- Moodle 4.0 or later
- PHP 7.4 or later
- VP Verifier service accessible from your Moodle server
- Moodle must be publicly accessible for webhook callbacks

## Installation

1. Copy the `kipmi` directory to `auth/kipmi` in your Moodle installation
2. Visit Site administration > Notifications to complete the installation
3. Enable the plugin at Site administration > Plugins > Authentication > Manage authentication

## Configuration

Navigate to Site administration > Plugins > Authentication > KIPMI Wallet Authentication

### Required Settings

**VP Verifier URL**
- The URL of the VP Verifier service for OIDC4VP verification
- This service must be accessible from your Moodle server

### Optional Settings

**Moodle Base URL**
- The public URL of your Moodle installation, used for VP Verifier webhook callbacks
- Default: Uses `$CFG->wwwroot`
- Set this if Moodle is behind a proxy or has a different public URL

**Login button label**
- Text displayed on the KIPMI login button
- Default: "Login with KIPMI"

**Credential name**
- The name of the verifiable credential type to request
- Default: `StudentStatusCredential`
- Examples: `StudentStatusCredential`, `identity-credential`

**Required fields**
- List of attribute fields to request from the credential, one per line
- Default: `given_name`, `family_name`, `studentId`, `email`
- Configure based on the attributes available in your credential type

**User identifier field**
- Which credential attribute to use for matching Moodle accounts
- Default: `studentId`
- Examples: `studentId`, `personal_administrative_number`, `email`

**User mapping field**
- Which Moodle user field to map the user identifier to
- Options: ID number (default), Username, or Email
- Recommendation: Use "ID number" for most deployments

**Auto-create users**
- Automatically create Moodle accounts for authenticated wallet users
- Enabled by default
- When disabled, users must be pre-created in Moodle with matching identifiers

**Default first name / Default last name**
- Fallback names when the credential doesn't provide name attributes
- Useful for credentials that may not include given_name or family_name

**Verify SSL certificates**
- Enable SSL certificate verification for VP Verifier connections
- Enabled by default
- Only disable for local development environments

## Usage

### For Administrators

1. Configure the VP Verifier URL and other settings
2. Ensure Moodle is publicly accessible for webhook callbacks
3. Enable the KIPMI authentication plugin
4. (Optional) Pre-create user accounts with identifiers matching wallet credentials

### For Users

1. Click the "Login with KIPMI" button on the Moodle login page
2. Scan the QR code with the KIPMI wallet mobile application
3. Approve the credential request in the wallet
4. You will be automatically logged in to Moodle

## Troubleshooting

### QR code doesn't appear
- Check VP Verifier URL is correct and accessible
- Verify Moodle can reach the VP Verifier service
- Check Moodle error logs for details

### Authentication times out
- Ensure Moodle is publicly accessible from VP Verifier
- Check that the Moodle Base URL setting is correct
- Verify firewall rules allow incoming webhook requests

### "User not found" error
- Enable auto-create users setting, OR
- Pre-create users with matching identifiers in the configured mapping field

### SSL certificate errors
- Install valid SSL certificates on VP Verifier, OR
- Disable SSL verification in plugin settings (development only)

## Privacy

This plugin implements Moodle's Privacy API. It shares minimal data with the VP Verifier service:
- Credential type being requested
- Callback URL for webhook delivery
- List of requested credential attributes

No personal data is stored persistently by the plugin. Temporary session data is automatically deleted after login or session expiry.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
