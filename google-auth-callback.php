<?php
session_start();
require 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('client_secret.json');
$client->addScope(Google_Service_Gmail::GMAIL_SEND);
$client->setRedirectUri('http://localhost/stuadm/google-auth-callback.php');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $token;
    header('Location: forgot_password.php');
    exit;
} else {
    header('Location: forgot_password.php');
    exit;
}
?>