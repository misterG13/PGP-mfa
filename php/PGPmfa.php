<?php

/**
 * Class  'PGPmfa()'
 * 
 * Author 'misterG13'
 * 
 * GNU Privacy Guard Functions:
 *   - https://www.php.net/manual/en/book.gnupg.php
 * 
 * Globals:
 *   - $_SESSION['pgp']['secret']
 *   - $_SESSION['pgp']['secretEncrypted']
 */
class PGPmfa
{
  protected function generateSecret(int $length = 16)
  {
    if (intval($length) < 16) {

      $length = 16;
    }

    $_SESSION['pgp']['secret'] = bin2hex(random_bytes($length));
    return $_SESSION['pgp']['secret'];
  }

  public function encryptSecret($publicKey)
  {
    putenv("GNUPGHOME=/tmp");

    $gpg = new gnupg();
    $key = $gpg->import($publicKey);

    $gpg->addencryptkey($key['fingerprint']);

    $secretKey = $this->generateSecret();
    $encrypted = $gpg->encrypt($secretKey);

    $_SESSION['pgp']['secretEncrypted'] = $encrypted;

    $gpg->clearencryptkeys();
    return true;
  }

  public function compareSecrets($input)
  {
    if (strcmp($input, $_SESSION['pgp']['secret']) === 0) {

      return true;
    }

    return false;
  }

  public function clearSecret()
  {
    unset($_SESSION['pgp']);
    return true;
  }

  public function testPgpkey($publicKey)
  {
    putenv("GNUPGHOME=/tmp");
    
    $gpg = new gnupg();
    $key = $gpg->import($publicKey);
    
    /*
    array $key:
      (
        [imported] => (int),
        [unchanged] => (int),
        [newuserids] => (int),
        [newsubkeys] => (int),
        [secretimported] => (int),
        [secretunchanged] => (int),
        [newsignatures] => (int),
        [skippedkeys] => (int),
        [fingerprint] => (string)
      )
    */

    //echo 'print_r key array: ' . print_r($key) . '<br>';

    // will print error from last function called:
    //echo 'get error: ' . $gpg->geterror() . '<br>';
    //echo 'get error details: <br>';
    //print_r($gpg->geterrorinfo());

    if ($key !== false) {

      if ($gpg->addencryptkey($key['fingerprint'])) {

        $gpg->clearencryptkeys();
        return true;
      }
    }

    return false;
  }
}
