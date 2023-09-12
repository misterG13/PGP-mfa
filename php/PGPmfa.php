<?php

namespace php;

/**
 * Class: PGPmfa(int $length, bool $rBtyes)
 *
 * Author: MisterG13
 *
 * Namespace: 'php'
 *
 * Globals:
 *   - $_SESSION['pgp']['secret']
 *   - $_SESSION['pgp']['secretEncrypted']
 *
 * GNU Privacy Guard Functions:
 *   - https://www.php.net/manual/en/book.gnupg.php
 */

class PGPmfa
{
    protected function generateSecret(int $length = 16, bool $rBytes = false)
    {
        // Set minimum length
        if (intval($length) < 16) {
            $length = 16;
        }

        /*
        After recent reading, openssl_random_pseudo_bytes()
        seems to be the prefered generation method.
        Now using the system's openSSL v3.0+. Patching previous
        CVE(s)
        */

        // PHP 5.6+ openssl_random_pseudo_bytes()
        // https://www.php.net/manual/en/function.openssl-random-pseudo-bytes.php
        if ($rBytes != true && version_compare(PHP_VERSION, '5.6.0') >= 0) {
            $bytes = openssl_random_pseudo_bytes($length, $cstrong);
            while ($cstrong != true) {
                $bytes = openssl_random_pseudo_bytes($length, $cstrong);
            }
            $hex   = bin2hex($bytes);
        }

        /*
        random_bytes() is useful when installing openSSL on
        the host is not an option. Claims have been made that this
        function has superior performance when hosted on a Windows box
        */

        // PHP 7.0+ random_bytes()
        // https://www.php.net/manual/en/function.random-bytes
        if ($rBytes == true && version_compare(PHP_VERSION, '7.0.0') >= 0) {
            $hex = bin2hex(random_bytes($length));
        }

        // Custom messaging around secret

        // Save to global
        $_SESSION['pgp']['secret'] = $hex;
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

        // no need to store public keys in system keyring
        // maybe move to __destruct() ?
        // $gpg->clearencryptkeys(); // removes ALL encryption (public) keys
        $gpg->deletekey($key['fingerprint'], true);
        return true;
    }

    public function compareSecrets($input)
    {
        if (!empty($_SESSION['pgp']['secret'])) {
            if (strcmp($input, $_SESSION['pgp']['secret']) === 0) {
                return true;
            }
        }

        return false;
    }

    public function clearSecret()
    {
        unset($_SESSION['pgp']);

        if (!isset($_SESSION['pgp'])) {
            return true;
        }

        return false;
    }

    public function testPgpkey($publicKey)
    {
        putenv("GNUPGHOME=/tmp");

        $gpg = new \gnupg();
        $key = $gpg->import($publicKey);

        /* Format of array $key
        Array
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

        /* Verify array contents
        echo '<pre>';
        print_r($key);
        echo '</pre>';
        */

        // DEBUGGING:
        // '$gpg->geterror()' will print error from last function called
        // echo 'get error: ' . $gpg->geterror() . '<br>';
        // echo 'get error details: <br>';
        // echo '<pre>';
        // print_r($gpg->geterrorinfo());
        // echo '</pre>';

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
