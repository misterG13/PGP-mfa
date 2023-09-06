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

namespace php;

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

        // '\' to escape the local namespace and access global class
        $gpg = new \gnupg();
        $key = $gpg->import($publicKey);

        $gpg->addencryptkey($key['fingerprint']);

        $secretKey = $this->generateSecret();
        $encrypted = $gpg->encrypt($secretKey);

        $_SESSION['pgp']['secretEncrypted'] = $encrypted;

        // $gpg->clearencryptkeys(); // removes ALL encryption (public) keys
        $gpg->deletekey($key['fingerprint'], true);
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

      /* format of array
      $key:
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

      /* verify $key format
      echo '<pre>';
      print_r($key);
      echo '</pre>';
      */

      // will print error from last function called:
      /* DEBUGING:
      echo 'get error: ' . $gpg->geterror() . '<br>';
      echo 'get error details: <br>';
      echo '<pre>';
      print_r($gpg->geterrorinfo());
      echo '</pre>';
      */

        if ($key !== false) {
            if ($gpg->addencryptkey($key['fingerprint'])) {
                //$gpg->clearencryptkeys(); // removes ALL encryption (public) keys
                $gpg->deletekey($key['fingerprint'], true);
                return true;
            }
        }

        return false;
    }
}
