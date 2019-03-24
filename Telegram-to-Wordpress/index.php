<?php

define('BOT_TOKEN', 'XXXXXX:XXXXXXXXXXXXXXX');

if (function_exists('fastcgi_finish_request')) {
    http_response_code(200);
    fastcgi_finish_request();
}

// require wp-load.php to use built-in WordPress functions
require __DIR__.'/../wp-load.php';
require __DIR__.'/../wp-admin/includes/image.php';
require __DIR__.'/functions.php';

date_default_timezone_set('Asia/Tehran');

$update = file_get_contents('php://input');
if (empty($update) || !isRequestIPValid()) {
    die;
}

$update = json_decode($update, true);

if(isset($update['channel_post'])) {
    $message = $update['channel_post'];
    if(isset($message['text'])) {
        $text = $message['text'];
        $new_post = [
            'post_title' => strtok($text, "\n"),
            'post_content' => '<p>'.preg_replace('/^.+\n/', '', $text).'</p>',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'post'
        ];
        wp_insert_post($new_post);
    } elseif(isset($message['photo']) && isset($message['caption'])) {
        $file_id = $message['photo'][count($message['photo']) - 1]['file_id'];
        $file_path = teleRequest('getFile', ['file_id' => $file_id])['file_path'];
        $file_url = 'https://api.telegram.org/file/bot'.BOT_TOKEN.'/'.$file_path;
        $image_path = __DIR__.basename($file_path);
        curl_download_file($file_url, $image_path);
        $text = $message['caption'];
        $new_post = [
            'post_title' => strtok($text, "\n"),
            'post_content' => '<p>'.preg_replace('/^.+\n/', '', $text).'</p>',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'post'
        ];
        $post_id = wp_insert_post($new_post);
        $upload = wp_upload_bits($image_path , null, file_get_contents($image_path, FILE_USE_INCLUDE_PATH));
        $imageFile = $upload['file'];
        $wpFileType = wp_check_filetype($imageFile, null);
        $attachment = [
            'post_mime_type' => $wpFileType['type'],
            'post_title' => sanitize_file_name($imageFile),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        $attachmentId = wp_insert_attachment($attachment, $imageFile, $post_id);
        $attachmentData = wp_generate_attachment_metadata($attachmentId, $imageFile);
        wp_update_attachment_metadata($attachmentId, $attachmentData);
        set_post_thumbnail($post_id, $attachmentId);
    }
}
