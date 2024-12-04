<?php

/**
 * PHP Version: 8.2.X @ https://www.php.net/releases/8.1/en.php
 * Standards:   PSR12 @ https://www.php-fig.org/psr/psr-12/
 *
 * Page formatting:
 *  - Indents as Spaces (x2)
 *  - End of Line Sequence using LF
 *  - UTF-8
 *
 * Author: MisterG13
 *
 * Description:
 *   - Builds the login form
 */

/* -------------------- PAGE PREPARATION ------------------------- */

ob_start();
session_start();

// Set a CONST as the root dir
defined('_ROOT_') || define('_ROOT_', __DIR__);

// Build local variables
$txtPGPkey = _ROOT_ . '/assets/publicPGPkey.txt';

// Include class file
include_once _ROOT_ . '/php/classes/PGPmfa.php';

// Insert namespace as an alias
use php\PGP\PGPmfa as PGPmfa;

// Verify class include
if (!class_exists('php\PGP\PGPmfa')) {
  // echo "class failed to include <br>";
}

/* -------------------- END PAGE PREPARATION --------------------- */


/* -------------------- PROGRAMMING ------------------------------ */
// Check SESSION for an encrypted message
if (empty($_SESSION['encryptedMessage'])) {

  // Import Public PGP Key
  if (file_exists($txtPGPkey)) {
    $publicKey = file_get_contents($txtPGPkey);
  }

  $pgpMFA = new PGPmfa($publicKey);

  $mfaCode = $pgpMFA->generateMfaCode();
  if ($mfaCode != false) {
    $_SESSION['mfaCode'] = $mfaCode;
  }

  $encryptedMessage = $pgpMFA->encryptMessage('Welcome to my secure website!', $mfaCode);
  if ($encryptedMessage != false) {
    $_SESSION['encryptedMessage'] = $encryptedMessage;
  }
}

// NO form submission (regular page visit)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  // placeholder
}

// YES form submission (html button 'submit' has been clicked)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // button 'submit' === 'newCode'
  if (strcmp($_POST['submit'], 'newCode') === 0) {

    // properly remove a session:
    // https://www.php.net/manual/en/function.session-unset.php#107089
    // session_start();
    session_unset();
    session_destroy();
    session_write_close();
    session_start();
    setcookie(session_name(), '', 0, '/');
    session_regenerate_id(true);

    header('Location: index.php', true, 302);
    ob_flush();
    exit;
  }

  // button 'submit' === 'authenticate'
  if (strcmp($_POST['submit'], 'authenticate') === 0) {

    if (empty($_POST['mfaCode'])) {
      $error = 'mfa code is empty';
    } else {
      if (strcmp($_POST['mfaCode'], $_SESSION['mfaCode']) === 0) {
        $error = 'passed mfa authentication';

        // Forward to 'account' area
        // header('Location: account.php', true, 302);
      } else {
        $error = 'failed mfa authentication';
      }
    }
  }
}

/* -------------------- END PROGRAMMING -------------------------- */


/* -------------------- VISUAL OUTPUT ---------------------------- */

// import html template:
$htmlUTF8Page = file_get_contents(_ROOT_ . '/html/authenticate.html', true);

// begin replacing keywords in html template:

// {CSS-LOGIN}
$html = '';
$loc = _ROOT_ . '/html/css/login.css';
if (file_exists($loc)) {
  $html  = '<style type="text/css">';
  $html .= file_get_contents($loc);
  $html .= '</style>';
}
$htmlUTF8Page = str_replace('{CSS-LOGIN}', $html, $htmlUTF8Page);

// {ERROR-DISPLAY}
$html         = (!empty($error)) ? $error : '';
$htmlUTF8Page = str_replace('{ERROR-DISPLAY}', ucwords($html), $htmlUTF8Page);

// {FORM-ACTION}
$html          = '';
$html          = '<form action="';
$html         .= htmlspecialchars($_SERVER['PHP_SELF']);
$html         .= '" method="post">';
$htmlUTF8Page  = str_replace('<form>', $html, $htmlUTF8Page);

// {MFA-CODE}
$html = '';
if (!empty($_SESSION['encryptedMessage'])) {
  $html = $_SESSION['encryptedMessage'];
}
$htmlUTF8Page = str_replace('{MFA-CODE}', $html, $htmlUTF8Page);

// display final html
echo $htmlUTF8Page;
ob_flush();

/* -------------------- END VISUAL OUTPUT ------------------------ */
