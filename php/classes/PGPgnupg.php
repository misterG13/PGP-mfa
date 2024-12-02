<?php

declare(strict_types=1);

namespace php\PGP;

class PGPgnupg
{
  protected $gpg;

  protected function __construct()
  {
    // Keyring storage directory (on host)
    putenv("GNUPGHOME=/tmp");

    if (!extension_loaded('gnupg')) {
      throw new \Exception("GnuPG PHP extension can not initialize");
    }

    // Initialize GnuPG
    $this->gpg = new \gnupg(); // VS Code reports 'undefined type': ignore

    // Check object
    if (!is_object($this->gpg)) {
      throw new \Exception("GnuPG object creation failed");
    }
  }

  /**
   * Handles calls to any methods/functions not defined by this local class.
   * Can be used to use bypass the local class and use PHP's
   * extension class GnuPG's methods/classes directly.
   * GnuPG Functions: https://www.php.net/manual/en/ref.gnupg.php
   */
  public function __call(string $method, mixed $arguments)
  {
    // echo "Method '$method' does not exist in this class. <br>";

    // Check for method in PHP's GnuPG class
    if ($this->methodExists($method)) {
      // echo "The method '$methodName' exists in the GnuPG class. <br>";
    } else {
      // echo "The method '$methodName' does not exist in the GnuPG class. <br>";
    }

    // echo "Called with arguments: " . implode(', ', $arguments) . "<br>";
  }

  private function methodExists(string $methodName): bool
  {
    $className = 'gnupg';

    // Check if the class exists but not initialized
    if (!class_exists($className)) {
      return false;
    }

    // Check if the method exists in the class
    if (method_exists($className, $methodName)) {
      return true;
    } else {
      return false;
    }
  }

  public function generateMfaCode(int $length = 16)
  {
    // $rBytes turns the use of random_bytes() on/off; default = false/off
    $rBytes = false;

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
      return bin2hex($bytes);
    }

    /*
      random_bytes() is useful when installing openSSL on
      the host is not an option. Claims have been made that this
      function has superior performance when hosted on a Windows box
    */

    // PHP 7.0+ random_bytes()
    // https://www.php.net/manual/en/function.random-bytes
    if ($rBytes == true && version_compare(PHP_VERSION, '7.0.0') >= 0) {
      return bin2hex(random_bytes($length));
    }

    return false;
  }
}
