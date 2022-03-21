<?php

/**
 * PHP Version  8.1.X @ https://www.php.net/releases/8.1/en.php
 * Standards    PSR12 @ https://www.php-fig.org/psr/psr-12/
 * 
 * Page formatting:
 *   - Indents as Spaces (x2)
 *   - End of Line Sequence using LF
 * 
 * Author       misterG13
 * 
 * Details:
 *   - Builds the login form
 */

ob_start();
session_start();

// set a CONST as the base dir
defined('_FILEPREP_') or define('_FILEPREP_', __DIR__);

// clear previous errors
$_SESSION['error'] = '';

// include from php folder
require_once _FILEPREP_ . '/php/PGPmfa.php';
$pgpMFA = new PGPmfa();

// check for form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

  // load 'pgpkey' before 'generateSecretMessage()'
  if (empty($_SESSION['pgpkey'])) {

    if (file_exists(_FILEPREP_ . '/assets/publicPGPkey.txt')) {

      $_SESSION['pgpkey'] = file_get_contents(_FILEPREP_ . '/assets/publicPGPkey.txt');
    } else {

      $_SESSION['error']  = 'Public PGPkey failed to load';
    }
  }

  // generate secret message
  if (empty($_SESSION['pgp']['secretEncrypted'])) {

    if (!$pgpMFA->encryptSecret($_SESSION['pgpkey'])) {

      $_SESSION['error'] = 'failed to encrypt MFA code';
    }
  }
}

// check for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // check which button was pressed
  if (strcmp($_POST['submit'], 'reload') === 0) {

    header('Location: index.php', true, 302);
  }

  // check which button was pressed
  if ((strcmp($_POST['submit'], 'doAuth') === 0)) {

    if (empty($_POST['pinCode'])) {

      $_SESSION['error'] = 'MFA code is empty';
    } elseif ($pgpMFA->compareSecrets($_POST['pinCode'])) {

      $_SESSION['error'] = 'passed MFA authentication';
      $pgpMFA->clearSecret();
    } else {

      $_SESSION['error'] = 'incorrect MFA code';
    }
  }
}

$htmlUTF8Page = file_get_contents(_FILEPREP_ . '/html/authenticate.html', true);

// {CSS-LOGIN}
$html = '';
if (file_exists('css/login.css')) {
  $html = '<style type="text/css">';
  $html .= file_get_contents(_FILEPREP_ . '/css/login.css');
  $html .= '</style>';
}
$htmlUTF8Page = str_replace('{CSS-LOGIN}', $html, $htmlUTF8Page);

// {ERROR-DISPLAY}
$html = '';
$html = $_SESSION['error'];
$htmlUTF8Page = str_replace('{ERROR-DISPLAY}', $html, $htmlUTF8Page);

// {FORM-ACTION}
$html  = '';
$html  = '<form action="';
$html .= htmlspecialchars($_SERVER['PHP_SELF']);
$html .= '"  method="post">';
$htmlUTF8Page = str_replace('<form>', $html, $htmlUTF8Page);

// {MFA-CODE}
$html = '';
$html = $_SESSION['pgp']['secretEncrypted'];
$htmlUTF8Page = str_replace('{MFA-CODE}', $html, $htmlUTF8Page);

echo $htmlUTF8Page;

ob_flush();
