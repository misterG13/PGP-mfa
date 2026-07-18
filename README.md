# PGP-MFA: Multi-Factor Authentication with PGP

PHP-based Multi-Factor Authentication using PGP public keys for web applications.

## Overview

A second form of authentication for highly secure web applications. Users generate their own PGP key pairs on a local machine. The public key is provided during sign up, eliminating the need to remember passwords and removing the liability of storing credentials on the server.

This project uses the PHP `gnupg` extension (GNU Privacy Guard) to import, encrypt, and validate PGP keys.

## Features

- PGP key import, parsing, and validation
- Encrypted MFA code generation and delivery
- Timing-safe MFA code verification
- CSRF token protection
- Isolated per-instance GPG keyrings (no shared state)
- SHA-256 hashed session storage

## Requirements

- PHP 8.0+ ([download](https://www.php.net/downloads))
- php-gnupg extension ([manual](https://www.php.net/manual/en/book.gnupg.php))
- GnuPG installed on the host system ([Install-GnuPG guide](guides/Install-GnuPG/README.md))

## Installation

1. Clone the repository
2. On a local machine (not the host), generate a PGP key pair
3. Copy your public key into `assets/publicPGPkey.txt`
4. Open `index.php` in a web browser
5. On success, you will see an encrypted message in the textarea
6. Copy the message to your local machine and decrypt it with your private key
7. Enter the decrypted code to authenticate

## Usage

### Web Interface

Open `index.php` in a browser. The page generates an MFA code, encrypts it with your public PGP key, and displays the ciphertext. Decrypt it locally and enter the code to authenticate.

### Programmatic

```php
use Classes\PGP\PGPMfa;

$pgpMfa = new PGPMfa($publicKey);

// Generate a 16-byte hex MFA code
$mfaCode = PGPMfa::generateMfaCode();

// Encrypt a message with the MFA code appended
$encrypted = $pgpMfa->encryptMessage('Your verification message', $mfaCode);

// Verify a user-supplied code against a stored hash
$valid = PGPMfa::verifyMfaCode($userInput, $storedHash);
```

## Project Structure

```
PGP-mfa/
├── assets/
│   └── publicPGPkey.txt      # Your PGP public key
├── html/
│   ├── authenticate.html      # Auth form template
│   └── css/
│       └── authenticate.css   # Form styles
├── php/
│   └── classes/
│       └── pgp/
│           ├── PGPMfa.php     # MFA encryption and verification
│           └── PGPgnupg.php   # Base GnuPG wrapper
├── guides/
│   └── Install-GnuPG/         # GnuPG installation guide
└── index.php                  # Entry point
```

## Guides

- [Install GnuPG](guides/Install-GnuPG/README.md)
- [Usage Examples](guides/Usage-PGPmfa/README.md)

## Author

MisterG13
