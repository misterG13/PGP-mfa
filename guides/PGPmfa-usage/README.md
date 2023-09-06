# 'PGPmfa()' class examples and basic uses

## Include PGPmfa class to your .php file:
    * Example:
        - $file = 'PGPmfa.php';
        - if ((require_once $file) == true) {
            // initialize a new class instance
            $pgpMFA = new php\PGPmfa();
        }

## Import a Public PGPKey:
    * Example:
        - $pgpkey = file_get_contents('publicPGPkey.txt');

## Generate a secret:
    * Example:
        - $pgpMFA->encryptSecret($pgpkey);

## Display the encrypted secret (for the user to see):
    * Example:
        - echo $_SESSION['pgp']['secretEncrypted'];

## Authenticating the decrypted code (from user):
    * Example:
        - if ($pgpMFA->compareSecrets($_POST['usersSecret'])) {
            // success; clear secret
            $pgpMFA->clearSecret();

            // move to 'login' area
            header('Location: account.php', true, 302);
        }