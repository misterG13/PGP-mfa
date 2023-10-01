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

// set a CONST as the root dir
defined('_ROOT_') || define('_ROOT_', __DIR__);

// local variables
$txtPGPkey = _ROOT_ . '/assets/publicPGPkey.txt';

// include class file
include_once _ROOT_ . '/php/PGPmfa.php';

// Insert namespace and alias
use php\PGPmfa as PGPmfa;

/* -------------------- END PAGE PREPARATION --------------------- */


/* -------------------- PROGRAMMING ------------------------------ */
// DEBUGGING:
/* echo '<pre>';
print_r($_SESSION);
echo '</pre>'; */

// Every page load; Verify class include
if (class_exists('php\PGPmfa')) {

  // Memory holds serialized class object (string)
  if (!empty($_SESSION['php']['PGPmfa']['objStorage'])) {

    // unserialize string from memory; restores to class object
    $pgpMFA = unserialize($_SESSION['php']['PGPmfa']['objStorage']);
  }

  // Memory empty; start a new class object
  if (empty($_SESSION['php']['PGPmfa']['objStorage'])) {

    // Import Public PGP Key
    if (file_exists($txtPGPkey)) {

      // Load 'pgpkey'    
      $publicKey = file_get_contents($txtPGPkey);

      // Verify contents
      if (!empty($publicKey)) {

        // Instantiate
        $pgpMFA = new PGPmfa($publicKey, 'Welcome to my website!' . "\n");
      } else {
        $error = 'public pgpkey failed to load';
      }
    }
  }

  // Pull encrypted secret message from object
  $_SESSION['php']['PGPmfa']['encrypted'] = $pgpMFA->getSecretMessageEncrypted();
} else {
  $error = 'class failed to include';
}

// NO form submission (regular page visit)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  // placeholder
}

// YES form submission (html button 'submit' has been clicked)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // button 'submit' === 'reload'
  if (strcmp($_POST['submit'], 'reload') === 0) {

    // Clear object 1st; class destructor evades session destroy
    unset($pgpMFA);

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

  // button 'submit' === 'doAuth'
  if (strcmp($_POST['submit'], 'doAuth') === 0) {

    if (empty($_POST['pinCode'])) {

      $error = 'mfa code is empty';
    } elseif ($pgpMFA->compareSecrets($_POST['pinCode'])) {

      $error = 'passed mfa authentication';

      // Remove object; remove serialization
      unset($pgpMFA, $_SESSION['php']['PGPmfa']);
    } else {

      $error = 'incorrect mfa code';
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
if (file_exists('css/login.css')) {
  $html  = '<style type="text/css">';
  $html .= file_get_contents(_ROOT_ . '/css/login.css');
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
if (!empty($_SESSION['php']['PGPmfa']['encrypted'])) {
  $html = $_SESSION['php']['PGPmfa']['encrypted'];
}
$htmlUTF8Page = str_replace('{MFA-CODE}', $html, $htmlUTF8Page);

// display final html
echo $htmlUTF8Page;
ob_flush();

/* -------------------- END VISUAL OUTPUT ------------------------ */
