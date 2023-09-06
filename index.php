<?php

/**
 * PHP Version  8.1.X @ https://www.php.net/releases/8.1/en.php
 * Standards    PSR12 @ https://www.php-fig.org/psr/psr-12/
 *
 * Page formatting:
 *   - Indents as Spaces (x4)
 *   - End of Line Sequence using LF
 *
 * Author       misterG13
 * Details:
 *   - Builds the login form
 */

ob_start();
session_start();

// set a CONST as the root dir:
defined('_ROOT_') || define('_ROOT_', __DIR__);

// include class file:
$file = _ROOT_ . '/php/PGPmfa.php';
if ((require_once $file) == true) {
    // initialize a new class instance
    $pgpMFA = new php\PGPmfa();
    // DEBUGGING:
    // if ($pgpMFA) {
    //     echo 'class initiated';
    //     echo '<br>';
    // } else {
    //     echo 'class failed to initialize';
    //     echo '<br>';
    // }
} else {
    // DEBUGGING:
    // echo 'class failed to include file';
    // echo '<br>';
}

// NO form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 1st load 'pgpkey'
    if (file_exists(_ROOT_ . '/assets/publicPGPkey.txt')) {
        $pgpkey = file_get_contents(_ROOT_ . '/assets/publicPGPkey.txt');
    } else {
        $error  = 'public pgpkey failed to load';
    }

    // 2nd generate secret message
    if (empty($_SESSION['pgp']['secretEncrypted'])) {
        if (!$pgpMFA->encryptSecret($pgpkey)) {
            $error = 'failed to encrypt mfa code';
        }
    }
}

// YES form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // button 'submit' === 'reload'
    if (strcmp($_POST['submit'], 'reload') === 0) {
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
        exit;
        ob_flush();
    }

    // button 'submit' === 'doAuth'
    if (strcmp($_POST['submit'], 'doAuth') === 0) {
        if (empty($_POST['pinCode'])) {
            $error = 'mfa code is empty';
        } elseif ($pgpMFA->compareSecrets($_POST['pinCode'])) {
            $error = 'passed mfa authentication';
            $pgpMFA->clearSecret();
        } else {
            $error = 'incorrect mfa code';
        }
    }
}

// import html template:
$htmlUTF8Page = file_get_contents(_ROOT_ . '/html/authenticate.html', true);

// begin replacing keywords in html template:
// {CSS-LOGIN}
$html = '';
if (file_exists('css/login.css')) {
    $html = '<style type="text/css">';
    $html .= file_get_contents(_ROOT_ . '/css/login.css');
    $html .= '</style>';
}
$htmlUTF8Page = str_replace('{CSS-LOGIN}', $html, $htmlUTF8Page);

// {ERROR-DISPLAY}
$html = (!empty($error)) ? ucwords($error) : '';
$htmlUTF8Page = str_replace('{ERROR-DISPLAY}', $html, $htmlUTF8Page);

// {FORM-ACTION}
$html  = '';
$html  = '<form action="';
$html .= htmlspecialchars($_SERVER['PHP_SELF']);
$html .= '"  method="post">';
$htmlUTF8Page = str_replace('<form>', $html, $htmlUTF8Page);

// {MFA-CODE}
$html = '';
if (!empty($_SESSION['pgp']['secretEncrypted'])) {
    $html = $_SESSION['pgp']['secretEncrypted'];
}
$htmlUTF8Page = str_replace('{MFA-CODE}', $html, $htmlUTF8Page);

// display final html
echo $htmlUTF8Page;
ob_flush();
