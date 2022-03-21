# PGPmfa() a PHP Class for PGP
Multi-factor Authentication using a Public PGP key for web based applications

## Multi-factor Authentication with PGP
Second form (or main form) of authentication to access highly secure web applications. 

A user generates there own PGP key pairs, on a local machine. The Public Key portion of the pair will be required during user sign up. This eliminates the need to remember several passwords and removes liability from a web application having to store secure credentials.

This PHP Class interacts with the PHP module/extension known as GnuPG (GNU Privacy Guard). This software allows the web host to import, encrypt/decrypt and test the validity of PGP keys.

# Requirements
  * 1st Option: Web Host with 'gnupg' or 'gnupg2' and PHP version 7 or better installed
  * 2nd Option: Web Host with admin/root access, needed to:
    - Install/Upgrade host to PHP version 7 or higher (https://www.php.net/downloads)
    - Install/Upgrade host to 'GnuPG/GnuPG2' (https://gnupg.org/)

# Installation
  * Clone Git or copy and upload files (maintain directory structure)
  * On a local machine (anywhere but the web host with this application)
    - Generate a test key pair with PGP (private + public keys)
  * Replace contents of '/assets/publicPGPkey.txt' with the previously generated, Public key
  * Open 'index.php' and follow the prompts
    - On success; you will see an encrypted message
    - Copy this message to your local machine with the Private key
    - Decrypt this message and copy the code inside
    - Paste the code as a password to continue the log in process

  * OR

  * Copy 'PGPmfa.php' from '/php/' folder and reference the class as needed in your own application
    - Two globals in $_SESSION are required and are noted at the top of the Class' page