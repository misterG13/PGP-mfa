# PGPmfa() a PHP Class for PGP

Multi-factor Authentication using a Public PGP key for web based applications

## Multi-factor Authentication with PGP

Second form (or main form) of authentication to access highly secure web applications.

A user generates there own PGP key pairs, on a local machine. The Public Key portion of the pair will be required during user sign up. This eliminates the need to remember several passwords and removes liability from a web application having to store secure credentials.

This PHP Class interacts with the PHP module/extension known as GnuPG (GNU Privacy Guard). This software allows the web host to import, encrypt/decrypt and test the validity of PGP keys.

# Requirements

- Linux Debian based system:
  - [Debian](https://www.debian.org/CD/http-ftp/#stable)
  - [Ubuntu Server](https://ubuntu.com/download/server#downloads)
- PHP v5.6 or newer (https://www.php.net/downloads)
- GnuPG installed on system ([guides/Install-GnuPG](https://github.com/misterG13/PGP-mfa/tree/main/guides/Install-GnuPG))

# Installation

- Clone Git
- On a local machine, not the host system:
  - Generate a test key pair with PGP (private + public keys)
- Replace contents of '/assets/publicPGPkey.txt' with your previously generated, Public key
- Open 'index.php' in your web browser and follow the prompts
  - On success; you will see an encrypted message
  - Copy this message to your local machine with the Private key
  - Decrypt this message and copy the code inside
  - Paste the code as a password to continue the log in process
- OR
- Copy 'PGPmfa.php' from '/php/' folder and reference the class as needed in your own application. The inline documentation is always growing in clarity.
