<?php

declare(strict_types=1);

namespace php\PGP;

// Include the parent class file
$parent = __DIR__ . '/PGPgnupg.php';

// Check if the parent class file exists before including it
if (file_exists($parent)) {
  require_once $parent;
} else {
  throw new \Exception("Unable to load parent class. File not found: " . $parent);
}

class PGPmfa extends PGPgnupg
{
  // Define the class properties
  protected $pgpkey;
  protected $fingerprint;

  public function __construct(string $pgpkey)
  {
    // Call the parent constructor to initialize $this->gnupg
    parent::__construct();

    // Verify that the GnuPG PHP extension is properly initialized
    if (!is_object($this->gpg)) {
      throw new \RuntimeException("Failed to initialize the GnuPG PHP extension.");
    }

    $this->pgpkey = $pgpkey;
  }

  public function encryptMessage(string $message = 'Welcome', string $mfaCode)
  {
    // Import $publicKey data as an array
    $keyData = $this->gpg->import($this->pgpkey);

    if (!is_array($keyData)) {
      return false;
    }

    /* echo '<pre>';
    print_r($keyData);
    echo '</pre>'; */

    // Save 'fingerprint'
    if (empty($keyData['fingerprint'])) {
      return false;
    }
    $this->fingerprint = $keyData['fingerprint'];

    // Combine welcome message and MFA code
    $mfaMessage = $message . "\n" . $mfaCode;
    // echo "Message to encrypt: $mfaMessage <br>";

    // Add to keyring; 'fingerprint' from the public key
    if ($this->gpg->addencryptkey($this->fingerprint)) {
      // echo "Key added to keyring <br>";
    }

    // Encrypt MFA message with pgpkey
    $encryptedMessage = $this->gpg->encrypt($mfaMessage);
    if ($encryptedMessage != false) {
      // echo "Encrypted message: <br> $encryptedMessage <br>";
    }

    // Remove public key from system keyring
    if ($this->gpg->deletekey($this->fingerprint, true)) {
      // echo "Key removed from keyring <br>";
      return $encryptedMessage;
    }

    return false;
  }
}
