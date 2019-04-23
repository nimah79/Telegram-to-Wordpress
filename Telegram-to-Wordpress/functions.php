<?php

function getRequestIP()
{
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip;
}

// Check if request is from Telegram
function isRequestIPValid()
{
    $lower_dec = (float) sprintf('%u', ip2long('149.154.167.197'));
    $upper_dec = (float) sprintf('%u', ip2long('149.154.167.233'));
    $ip_dec = (float) sprintf('%u', ip2long(getRequestIP()));
    if ($ip_dec < $lower_dec || $ip_dec > $upper_dec) {
        return false;
    }

    return true;
}

function teleRequest($method, $parameters = [])
{
    if (isset($parameters['reply_markup']['keyboard'])) {
        $parameters['reply_markup']['resize_keyboard'] = true;
    }
    foreach ($parameters as $key => &$val) {
        if (is_array($val)) {
            $val = json_encode($val);
        }
    }
    $ch = curl_init('https://api.telegram.org/bot'.BOT_TOKEN.'/'.$method);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);
    if (isset($response['result'])) {
        return $response['result'];
    }

    return false;
}

function curl_download_file($url, $file_path)
{
    $ch = curl_init($url);
    $fp = fopen($file_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
}
