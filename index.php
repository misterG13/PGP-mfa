<?php

/**
 * PHP Version: 8.2.X @ https://www.php.net/releases/8.2/en.php
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
 *   - PGP-based Multi-Factor Authentication (MFA)
 *   - Generates encrypted MFA codes for user verification
 */

/* -------------------- PAGE PREPARATION ------------------------- */

ob_start();
session_start();

// Generate CSRF token for form validation
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set a CONST as the root dir
defined('_ROOT_') || define('_ROOT_', __DIR__);

// Initialize error variable for template
$error = '';

// Build local paths
$txtPGPkey = _ROOT_ . '/assets/publicPGPkey.txt';

// Include PGP MFA class
include_once _ROOT_ . '/php/classes/pgp/PGPMfa.php';

// Insert namespace as an alias
use Classes\PGP\PGPMfa as PGPMfa;

/* -------------------- END PAGE PREPARATION --------------------- */


/* -------------------- PROGRAMMING ------------------------------ */
// Check SESSION for an encrypted message
if (empty($_SESSION['encryptedMessage'])) {

  // Import Public PGP Key
  $publicKey = '';
  if (file_exists($txtPGPkey)) {
    $publicKey = file_get_contents($txtPGPkey);
  }

  $pgpMFA = new PGPMfa($publicKey);

  $mfaCode = PGPMfa::generateMfaCode();
  $_SESSION['mfaCode'] = hash('sha256', $mfaCode);

  $encryptedMessage = $pgpMFA->encryptMessage('Welcome to my secure website!', $mfaCode);
  if ($encryptedMessage != false) {
    $_SESSION['encryptedMessage'] = $encryptedMessage;
  }
}

// Handle POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

  // Validate CSRF token
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $error = 'invalid request';
    error_log('index.php: CSRF token mismatch');
  } else {

    // button 'submit' === 'newCode'
    if (strcmp($_POST['submit'], 'newCode') === 0) {

      // properly remove a session:
      // https://www.php.net/manual/en/function.session-unset.php#107089
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
        error_log('index.php: mfa code input empty');
      } else {
        if (!isset($_SESSION['mfaCode'])) {
          $error = 'session expired';
          error_log('index.php: session mfaCode missing');
        } else if (PGPMfa::verifyMfaCode($_POST['mfaCode'], $_SESSION['mfaCode'])) {
          $error = 'passed mfa authentication';

          // Forward to 'account' area
          // header('Location: account.php', true, 302);
        } else {
          $error = 'failed mfa authentication';
          error_log('index.php: mfa authentication failed');
        }
      }
    }
  }
}

/* -------------------- END PROGRAMMING -------------------------- */


/* -------------------- VISUAL OUTPUT ---------------------------- */

// Import HTML template
$htmlUTF8Page = file_get_contents(_ROOT_ . '/html/authenticate.html');

// Replace template placeholders

// {CSS-AUTHENTICATE}
$html = '';
$loc = _ROOT_ . '/html/css/authenticate.css';
if (file_exists($loc)) {
  $html  = '<style type="text/css">';
  $html .= file_get_contents($loc);
  $html .= '</style>';
}
$htmlUTF8Page = str_replace('{CSS-AUTHENTICATE}', $html, $htmlUTF8Page);

// {ERROR-DISPLAY}
$html         = (!empty($error)) ? $error : '';
$htmlUTF8Page = str_replace('{ERROR-DISPLAY}', ucwords($html), $htmlUTF8Page);

// {FORM-ACTION}
$html          = '<form action="';
$html         .= htmlspecialchars($_SERVER['PHP_SELF']);
$html         .= '" method="post">';
$htmlUTF8Page  = str_replace('<form>', $html, $htmlUTF8Page);

// {CSRF-TOKEN}
$html = '<input type="hidden" name="csrf_token" value="'
  . htmlspecialchars($_SESSION['csrf_token']) . '">';
$htmlUTF8Page = str_replace('{CSRF-TOKEN}', $html, $htmlUTF8Page);

// {MFA-CODE}
$html = '';
if (!empty($_SESSION['encryptedMessage'])) {
  $html = $_SESSION['encryptedMessage'];
}
$htmlUTF8Page = str_replace('{MFA-CODE}', $html, $htmlUTF8Page);

// Render final HTML
echo $htmlUTF8Page;
ob_end_flush();

/* -------------------- END VISUAL OUTPUT ------------------------ */
