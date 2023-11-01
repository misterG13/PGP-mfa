<?php

namespace php;

/**
 * PGPmfa(string $publicKey, string $message = '')
 *
 * Author: MisterG13
 *
 * Namespace: 'php'
 *
 * GNU Privacy Guard Functions:
 *   - https://www.php.net/manual/en/book.gnupg.php
 */

class PGPmfa
{
  private $publicKey;
  private $fingerprint;
  private $secret;
  private $secretEncrypted;
  private $gnupg;

  /**
   * Import a user's public pgp key to begin. You can add a welcome message
   * that will show inside the encrypt message (optional).
   *
   * @param  string $publicKey
   * @param  string $message
   */
  public function __construct(string $publicKey, string $message = '')
  {
    // Keyring storage directory (on host)
    putenv("GNUPGHOME=/tmp");

    // '\' to escape the local namespace and access global class
    $this->gnupg = new \gnupg();

    // Save the user's public key to memory
    $this->publicKey = $publicKey;

    // Pull 'fingerprint' from 'publicKey'
    if (!$this->pullFingerprint()) {
      return false;
    }

    // Generate 'secret' code
    if (!$this->genSecret()) {
      return false;
    }

    // Format custom messaging (optional)
    if (empty($message)) {
      $message = 'Welcome to my Website!' . "\n";
    }

    // Encrypt 'secret' with user's 'publicKey' 'fingerprint'
    if (!$this->encryptSecretMessage($message)) {
      return false;
    }
  }

  private function pullFingerprint()
  {
    // Global object alias
    $gnupg = $this->gnupg;

    // Import $publicKey data as an array
    $keyData = $gnupg->import($this->publicKey);

    /* $keyData = array(
      [imported]        => (int),
      [unchanged]       => (int),
      [newuserids]      => (int),
      [newsubkeys]      => (int),
      [secretimported]  => (int),
      [secretunchanged] => (int),
      [newsignatures]   => (int),
      [skippedkeys]     => (int),
      [fingerprint]     => (string)
    ) */

    if (!empty($keyData['fingerprint'])) {

      // Save 'fingerprint' to object
      $this->fingerprint = $keyData['fingerprint'];
      return true;
    }

    return false;
  }

  private function genSecret(int $length = 16, bool $rBytes = false)
  {
    // $rBytes turns the use of random_bytes() on/off; default = false/off

    // Set minimum length
    if (intval($length) < 16) {
      $length = 16;
    }

    /*
      After recent reading, openssl_random_pseudo_bytes()
      seems to be the preferred generation method.
      Now using the system's openSSL v3.0+. Patching previous
      CVE(s)
    */

    // PHP 5.6+ openssl_random_pseudo_bytes()
    // https://www.php.net/manual/en/function.openssl-random-pseudo-bytes.php
    if ($rBytes != true && version_compare(PHP_VERSION, '5.6.0') >= 0) {
      $bytes = openssl_random_pseudo_bytes($length, $cryptoStrong);
      while ($cryptoStrong != true) {
        $bytes = openssl_random_pseudo_bytes($length, $cryptoStrong);
      }
      $this->secret = bin2hex($bytes);
    }

    /*
      random_bytes() is useful when installing openSSL on
      the host is not an option. Claims have been made that this
      function has superior performance when hosted on a Windows box
    */

    // PHP 7.0+ random_bytes()
    // https://www.php.net/manual/en/function.random-bytes
    if ($rBytes == true && version_compare(PHP_VERSION, '7.0.0') >= 0) {
      $this->secret = bin2hex(random_bytes($length));
    }

    if (!empty($this->secret)) {
      return true;
    }

    return false;
  }

  private function encryptSecretMessage(string $message = '')
  {
    // Global object alias
    $gnupg = $this->gnupg;

    // Add to keyring; 'fingerprint' from the public key
    $gnupg->addencryptkey($this->fingerprint);

    // Custom messaging around secret
    $message = $message . $this->secret;

    // Uses public key from last addencryptkey() to encrypt secret
    // Save the encrypted message to memory
    $this->secretEncrypted = $gnupg->encrypt($message);

    // Remove public key from system keyring
    if ($gnupg->deletekey($this->fingerprint, true)) {
      return true;
    }

    return false;
  }

  public function getSecretMessageEncrypted()
  {
    if (!empty($this->secretEncrypted)) {
      return $this->secretEncrypted;
    }

    return '';
  }

  public function compareSecrets(string $input)
  {
    if (!empty($this->secret)) {
      if (strcmp($input, $this->secret) === 0) {
        return true;
      }
    }

    return false;
  }

  /**
   * Serialize current object to $_SESSION['php']['PGPmfa']['objStorage']
   */
  public function __destruct()
  {
    $_SESSION['php']['PGPmfa']['objStorage'] = serialize($this);
  }
}
