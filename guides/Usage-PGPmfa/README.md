# Using the PGP-MFA Classes

The `PGPMfa` class provides PGP-based Multi-Factor Authentication. It handles key import, MFA code generation, message encryption, and verification.

## Setup

Include the class file and use the namespace:

```php
include_once 'php/classes/pgp/PGPMfa.php';

use Classes\PGP\PGPMfa as PGPMfa;
```

Load a PGP public key and instantiate the class:

```php
$publicKey = file_get_contents('assets/publicPGPkey.txt');

$pgpMfa = new PGPMfa($publicKey);
```

## Validate a PGP Key

Before using a key, verify it can be imported and parsed:

```php
if ($pgpMfa->testPgpKey()) {
    echo 'Key is valid';
} else {
    echo 'Key is invalid';
}
```

## Read Key Information

Extract the name and email from the key's User ID:

```php
$name = $pgpMfa->getKeyName();
$email = $pgpMfa->getKeyEmail();

if ($name !== false) {
    echo 'Key holder: ' . $name;
}

if ($email !== false) {
    echo 'Email: ' . $email;
}
```

## Generate and Encrypt an MFA Code

Generate a cryptographically secure MFA code, then encrypt it with the PGP public key:

```php
// Generate a 16-byte hex MFA code
$mfaCode = PGPMfa::generateMfaCode();

// Encrypt a message with the MFA code appended
$encrypted = $pgpMfa->encryptMessage('Your verification code', $mfaCode);

if ($encrypted !== false) {
    // Display to user (they decrypt with their private key)
    echo $encrypted;

    // Store hash in session (never store the raw code)
    $_SESSION['mfaCode'] = hash('sha256', $mfaCode);
}
```

## Verify User Authentication

After the user decrypts the message and submits the code, verify it against the stored hash:

```php
if (PGPMfa::verifyMfaCode($_POST['mfaCode'], $_SESSION['mfaCode'])) {
    echo 'Authentication successful';
} else {
    echo 'Invalid code';
}
```

The `verifyMfaCode()` method uses `hash_equals()` for timing-safe comparison, preventing timing attacks.

## Complete Workflow

A full example from key loading to authentication:

```php
<?php
session_start();

use Classes\PGP\PGPMfa as PGPMfa;

$publicKey = file_get_contents('assets/publicPGPkey.txt');
$pgpMfa = new PGPMfa($publicKey);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['mfaCode']) && isset($_SESSION['mfaCode'])) {

        if (PGPMfa::verifyMfaCode($_POST['mfaCode'], $_SESSION['mfaCode'])) {
            echo 'Passed authentication';
            // Forward to account area
            // header('Location: account.php', true, 302);
            // exit;
        } else {
            echo 'Failed authentication';
        }
    }
}

// Generate new MFA code if none exists
if (empty($_SESSION['encryptedMessage'])) {

    $mfaCode = PGPMfa::generateMfaCode();
    $_SESSION['mfaCode'] = hash('sha256', $mfaCode);

    $encrypted = $pgpMfa->encryptMessage('Your verification code', $mfaCode);

    if ($encrypted !== false) {
        $_SESSION['encryptedMessage'] = $encrypted;
    }
}
```

## API Reference

| Method | Type | Description |
|--------|------|-------------|
| `__construct(string $pgpkey)` | instance | Initialize with ASCII-armored PGP public key |
| `testPgpKey(): bool` | instance | Validate that the key can be imported |
| `getKeyName(): string\|false` | instance | Get name from key's primary User ID |
| `getKeyEmail(): string\|false` | instance | Get email from key's primary User ID |
| `encryptMessage(string $message, string $mfaCode): string\|false` | instance | Encrypt message with MFA code appended |
| `generateMfaCode(int $length = 16): string` | static | Generate cryptographically secure hex code |
| `verifyMfaCode(string $userInput, string $storedHash): bool` | static | Timing-safe comparison against SHA-256 hash |
