<?php declare(strict_types=1);

namespace Classes\PGP;

require_once __DIR__ . '/PGPgnupg.php';

/**
 * MFA (Multi-Factor Authentication) using PGP encryption.
 *
 * Handles PGP key import, User ID parsing, MFA code generation,
 * and message encryption for PGP-based two-factor authentication.
 */
class PGPMfa extends PGPgnupg
{
  /**
   * ASCII-armored PGP public key string.
   *
   * @var string
   */
  private string $pgpkey;

  /**
   * Cached result of the key import/parse operation.
   *
   * @var array{fingerprint: string, full_uid: string, name: string, email: string, comment: string}|null
   */
  private ?array $keyDetails = null;

  /**
   * @param string $pgpkey The public PGP key as an ASCII-armored string
   */
  public function __construct(string $pgpkey)
  {
    parent::__construct();
    $this->pgpkey = $pgpkey;
  }

  /**
   * Imports the PGP key, extracts the fingerprint and primary User ID,
   * and caches the result.
   *
   * The key remains in the keyring until deleteKey() is called or the
   * object is destroyed (which removes the entire temp directory).
   *
   * @return array{fingerprint: string, full_uid: string, name: string, email: string, comment: string}|null
   *               Associative array with key and user info, or null on failure
   */
  private function importKey(): ?array
  {
    if ($this->keyDetails !== null) {
      return $this->keyDetails;
    }

    $keyData = $this->gpg->import($this->pgpkey);

    if (!is_array($keyData) || empty($keyData['fingerprint'])) {
      error_log('Failed to import PGP key or missing fingerprint');
      return null;
    }

    $fingerprint = $keyData['fingerprint'];

    $keyInfo = $this->gpg->keyinfo($fingerprint);

    if (empty($keyInfo) || !is_array($keyInfo) || !isset($keyInfo[0]['uids'])) {
      error_log('Failed to get key information for fingerprint: ' . $fingerprint);
      $this->gpg->deletekey($fingerprint, true);
      return null;
    }

    $primaryUid = $keyInfo[0]['uids'][0] ?? null;

    if (!$primaryUid) {
      error_log('No primary UID found in key info');
      $this->gpg->deletekey($fingerprint, true);
      return null;
    }

    $uidString = $primaryUid['uid'];

    $result = [
      'fingerprint' => $fingerprint,
      'full_uid'    => $uidString,
      'name'        => '',
      'email'       => '',
      'comment'     => ''
    ];

    if (preg_match('/<([^>]+)>/', $uidString, $emailMatches)) {
      $result['email'] = $emailMatches[1];
    }

    if (preg_match('/\(([^)]+)\)/', $uidString, $commentMatches)) {
      $result['comment'] = $commentMatches[1];
    }

    $namePart       = preg_replace('/\s*[<(].*$/', '', $uidString);
    $result['name'] = trim($namePart);

    $this->keyDetails = $result;
    return $this->keyDetails;
  }

  /**
   * Deletes the imported key from the keyring and clears the cache.
   *
   * Safe to call multiple times — no-op if no key was imported.
   */
  private function deleteKey(): void
  {
    if ($this->keyDetails !== null) {
      $this->gpg->deletekey($this->keyDetails['fingerprint'], true);
      $this->keyDetails = null;
    }
  }

  /**
   * Retrieves the email address from the PGP key's primary User ID.
   *
   * @return string|false The email, or false on failure
   */
  public function getKeyEmail(): string|false
  {
    $details = $this->importKey();
    return $details['email'] ?? false;
  }

  /**
   * Retrieves the name from the PGP key's primary User ID.
   *
   * @return string|false The name, or false on failure
   */
  public function getKeyName(): string|false
  {
    $details = $this->importKey();
    return $details['name'] ?? false;
  }

  /**
   * Generates a cryptographically secure MFA code.
   *
   * Enforces a minimum of 16 bytes (output hex string is length * 2 chars).
   *
   * @param int $length Number of random bytes (default 16, min 16)
   * @return string Hex-encoded random string
   * @throws \RuntimeException If the CSPRNG is unavailable
   */
  public static function generateMfaCode(int $length = 16): string
  {
    return bin2hex(random_bytes(max(16, $length)));
  }

  /**
   * Encrypts a message with an embedded MFA code using the stored PGP public key.
   *
   * The MFA code is appended to the message with a newline separator.
   * The key is imported, used for encryption, then explicitly deleted.
   *
   * @param string $message The message text (e.g. "Your verification code")
   * @param string $mfaCode The MFA code appended after a newline
   * @return string|false ASCII-armored encrypted message, or false on failure
   */
  public function encryptMessage(string $message, string $mfaCode): string|false
  {
    $details = $this->importKey();

    if (!$details) {
      return false;
    }

    $mfaMessage = $message . "\n" . $mfaCode;

    if (!$this->gpg->addencryptkey($details['fingerprint'])) {
      error_log('Failed to add encryption key: ' . ($this->gpg->geterror() ?? 'Unknown error'));
      $this->deleteKey();
      return false;
    }

    $encryptedMessage = $this->gpg->encrypt($mfaMessage);

    $this->deleteKey();

    if ($encryptedMessage === false) {
      error_log('Failed to encrypt message: ' . ($this->gpg->geterror() ?? 'Unknown error'));
      return false;
    }

    return $encryptedMessage;
  }

  /**
   * Validates that the PGP key can be imported and parsed.
   *
   * @return bool True if the key is valid, false otherwise
   */
  public function testPgpKey(): bool
  {
    $valid = $this->importKey() !== null;
    $this->deleteKey();
    return $valid;
  }

  /**
   * Timing-safe comparison of a user-supplied MFA code against a stored hash.
   *
   * The stored hash is expected to already be hash('sha256', ...).
   *
   * @param string $userInput  Raw MFA code from the user
   * @param string $storedHash SHA-256 hash stored in the session
   * @return bool True if the codes match
   */
  public static function verifyMfaCode(string $userInput, string $storedHash): bool
  {
    return hash_equals($storedHash, hash('sha256', $userInput));
  }
}
