<?php
require 'vendor/autoload.php';

use Google_Client;

session_start();

$client = new Google_Client();
$client->setAuthConfig('client_secret.json');
$client->addScope(Google_Service_Gmail::MAIL_GOOGLE_COM);
$client->setRedirectUri('http://localhost/stuadm/oauth2callback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $token;
    header('Location: http://localhost/stuadm/forgot_password.php');
    exit;
} else {
    echo "Error: No code received.";
}
?>