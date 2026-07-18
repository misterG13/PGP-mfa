<?php

declare(strict_types=1);

namespace Classes\PGP;

/**
 * Base wrapper for the PHP gnupg extension.
 *
 * Creates an isolated per-instance temp directory for the GPG keyring
 * and delegates all gnupg operations to the underlying \gnupg object.
 */
class PGPgnupg
{
  protected ?\gnupg $gpg = null;
  private string $tempDir;

  /**
   * Creates an isolated temp keyring and initializes the gnupg object.
   *
   * @throws \RuntimeException If gnupg extension is not loaded or object creation fails
   * @see __destruct()
   */
  public function __construct()
  {
    $this->tempDir = sys_get_temp_dir() . '/gnupg_' . md5(__FILE__ . getmypid() . spl_object_id($this));
    if (!is_dir($this->tempDir)) {
      mkdir($this->tempDir, 0700, true);
    }
    putenv('GNUPGHOME=' . $this->tempDir);

    if (!extension_loaded('gnupg')) {
      throw new \RuntimeException('GnuPG PHP extension cannot be initialized');
    }

    $this->gpg = new \gnupg();

    if (!is_object($this->gpg)) {
      throw new \RuntimeException('GnuPG object creation failed');
    }
  }

  /**
   * Proxies unknown method calls to the underlying \gnupg object.
   *
   * @throws \BadMethodCallException If the method does not exist on \gnupg
   */
  public function __call(string $method, mixed $arguments)
  {
    if (!$this->methodExists($method)) {
      throw new \BadMethodCallException("Method '$method' does not exist in GnuPG class.");
    }

    return $this->gpg->$method(...$arguments);
  }

  /**
   * Checks if a method exists on the PHP gnupg class.
   */
  private function methodExists(string $method): bool
  {
    return method_exists('gnupg', $method);
  }

  /**
   * Recursively removes a directory and all its contents.
   */
  private function removeDirectory(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
      if ($file->isDir()) {
        @rmdir($file->getPathname());
      } else {
        @unlink($file->getPathname());
      }
    }

    @rmdir($dir);
  }

  /**
   * Removes the temp keyring directory created in the constructor.
   */
  public function __destruct()
  {
    $this->removeDirectory($this->tempDir);
  }
}
