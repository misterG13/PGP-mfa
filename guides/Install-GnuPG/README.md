# Installing GnuPG for PHP

GnuPG (GNU Privacy Guard) is required for the `php-gnupg` extension, which this project uses for PGP encryption and key management.

## Requirements

- GnuPG 2.x installed on the system
- PHP 8.0+ with the `php-gnupg` extension

## Debian / Ubuntu

GnuPG and the PHP extension are available from the default repositories.

```bash
sudo apt-get update
sudo apt-get install -y php-gnupg gnupg2
```

To install for a specific PHP version (e.g., 8.3):

```bash
sudo apt-get install -y php8.3-gnupg gnupg2
```

## Fedora / RHEL

```bash
sudo dnf install php-gnupg gnupg2
```

On older systems using `yum`:

```bash
sudo yum install php-gnupg gnupg2
```

## macOS

Install via Homebrew:

```bash
brew install gnupg
```

The PHP extension can be installed via PECL:

```bash
pecl install gnupg
```

Then add to your `php.ini`:

```ini
extension=gnupg.so
```

## Verify Installation

**CLI method (recommended):**

```bash
php -m | grep gnupg
```

If `gnupg` appears in the output, the extension is loaded.

**Web method:**

Create a temporary file in your web root:

```bash
echo '<?php phpinfo();' | sudo tee phpinfo.php
```

Open `phpinfo.php` in a browser and search for `gnupg`. If the section exists, the extension is active.

Delete the file immediately after verification:

```bash
sudo rm -f phpinfo.php
```

> Never leave `phpinfo()` exposed in a production or public environment.

## Windows

Install [Gpg4win](https://gpg4win.org/download.html), which bundles GnuPG and required components for Windows.

For the PHP extension on Windows, see the [php-gnupg PECL page](https://pecl.php.net/package/gnupg).

## Resources

- [GnuPG Downloads](https://gnupg.org/download/)
- [php-gnupg Manual](https://www.php.net/manual/en/book.gnupg.php)
- [GnuPG Documentation](https://gnupg.org/documentation/)
- [Project Installation Guide](../../README.md)
