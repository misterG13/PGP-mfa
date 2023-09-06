# 'GnuPG' a PHP extension, helpful install tips

## Linux Debian based Server:
    Install via cli
        'sudo apt-get install -y php8.1-gnupg gnupg2'

## Verify GNUPG Extension in PHP:
    Create a new file in web root directory
        'sudo nano phpinfo.php'
    Add this line:
        'phpinfo();'
    Exit & Save
    Open phpinfo.php in a web browser
    Search for 'gnupg'
    If found = successful install
    ** DELETE this file, never have in production or public **
        'sudo rm -rf phpinfo.php'