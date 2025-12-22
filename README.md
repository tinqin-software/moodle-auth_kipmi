# KIPMI Wallet Authentication Plugin

A Moodle authentication plugin that enables users to log in using KIPMI wallet and Verifiable Credentials (OIDC4VP).

## Description

This plugin allows users to authenticate to Moodle by scanning a QR code with their KIPMI wallet application.

## Requirements

- Moodle 4.0 or later
- PHP 7.4 or later

## Installation

1. Copy the `kipmi` directory to `auth/kipmi` in your Moodle installation
2. Visit Site administration > Notifications to complete the installation
3. Enable the plugin at Site administration > Plugins > Authentication > Manage authentication

## Configuration

Navigate to Site administration > Plugins > Authentication > KIPMI Wallet Authentication

### Required Settings

**Backend URL**
- The Kipmi URL
- Example: `http://authentication-be:8081` or `https://auth.yourdomain.com`
- This service must be accessible from your Moodle server

### Optional Settings

**Login button label**
- Text displayed on the KIPMI login button
- Default: "Login with KIPMI"

**User mapping field**
- Which Moodle user field to map the `personal_administrative_number` credential to
- Options: ID number (default), Username, or Email
- Recommendation: Use "ID number" for most deployments

**Auto-create users**
- Automatically create Moodle accounts for authenticated wallet users
- Enabled by default
- When disabled, users must be pre-created in Moodle with matching ID numbers

**Verify SSL certificates**
- Enable SSL certificate verification for backend connections
- Enabled by default
- Only disable for local development environments

## Usage

### For Administrators

1. Configure the backend URL and other settings
2. Enable the KIPMI authentication plugin
3. (Optional) Pre-create user accounts with ID numbers matching wallet credentials

### For Users

1. Click the "Login with KIPMI" button on the Moodle login page
2. Scan the QR code with the KIPMI wallet mobile application
3. Approve the credential request in the wallet
4. You will be automatically logged in to Moodle



## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
