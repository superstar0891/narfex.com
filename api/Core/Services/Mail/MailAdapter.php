<?php

namespace Core\Services\Mail;

use Core\App;
use Core\Services\Redis\RedisAdapter;
use Engine\Debugger\Traceback;

const CACHE_MAILKEY_TPL = 'findiri_sendmail';
const BITCOINOVNET_CACHE_MAILKEY_TPL = 'bitcoinovnet_sendmail';
const MAIL_USER = 'noreply@findiri.com';
const MAIL_BITCOINOVNET_USER = 'support@bitcoinov.net';

class Templates {
    const CHANGE_EMAIL_CODE = 'change_email_code';
    const CHANGE_EMAIL = 'change_email';
    const SIMPLE = 'simple';
    const RESET_PASSWORD_CODE = 'reset_password_code';
    const RESET_PASSWORD = 'reset_password';
    const AUTH = 'auth';
    const AUTH_BITCOINOVNET = 'bitcoinovnet/auth';
    const CREATE_BITCOINOVNET = 'bitcoinovnet/create';
    const EXPIRED_BITCOINOVNET = 'bitcoinovnet/create';
    const DONE_BITCOINOVNET = 'bitcoinovnet/done';
    const REJECT_BITCOINOVNET = 'bitcoinovnet/reject';
    const MODERATION_BITCOINOVNET = 'bitcoinovnet/moderation';
    const WRONG_AMOUNT_BITCOINOVNET = 'bitcoinovnet/wrong_amount';
}

class MailAdapter {
    private static function makeTemplate($name, $params = []): string {
        $styles = [
            'title' => 'margin: 16px 0; font-weight: 600; font-size: 24px; line-height: 32px;',
            'subtitle' => 'margin: 16px 0; font-weight: 600; font-size: 18px; line-height: 24px;',
            'code' => 'margin-top: 16px;font-weight: 600;font-size: 24px;line-height: 32px;',
            'paragraph' => 'margin-top: 16px;',
            'link' => 'color: #4070FF; word-wrap: break-word;'
        ];

        if (strpos($name, 'bitcoinovnet') !== false) {
            $wrapper = file_get_contents(KERNEL_CONFIG['root'] . '/Core/Services/Mail/templates/bitcoinovnet/wrapper.html');
        } else {
            $wrapper = file_get_contents(KERNEL_CONFIG['root'] . '/Core/Services/Mail/templates/wrapper.html');
        }
        $content = file_get_contents(KERNEL_CONFIG['root'] . '/Core/Services/Mail/templates/' . $name . '.html');
        $content = preg_replace_callback('/\{lang\=(.*)\}/i', function ($matches) {
            return lang($matches[1]);
        }, $content);

        $keys = array_map(function ($row) {
            return "{{$row}}";
        }, array_keys($params));

        $content = str_replace($keys, array_values($params), $content);

        $wrapper = str_replace('{logo}', array_get_val($params, 'logo', ''), $wrapper);
        $result = str_replace('{content}', $content, $wrapper);
        $result = preg_replace_callback('/\{style\=(.*)\}/i', function ($matches) use ($styles) {
            return $styles[$matches[1]];
        }, $result);

        return $result;
    }

    private static function makeHeaders($email, $subject): string {
        $subject = 'Findiri: ' . $subject;

        $headers  = "From: " . MAIL_USER . "\r\n";
        $headers .= "To: " . $email . "\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Return-Path: " . MAIL_USER . "\r\n";
        $headers .= "Error-to: " . MAIL_USER . "\r\n";

        return $headers;
    }

    public static function send($email, $subject, $template, $params = []) {
        $params['logo'] = 'logo.png';
        self::sendEmail(CACHE_MAILKEY_TPL, $email, $subject, $template, $params);
    }

    public static function sendBitcoinovnet($email, $subject, $template, $params = []) {
        $params['logo'] = 'bitcoinovnet.png';
        self::sendEmail(BITCOINOVNET_CACHE_MAILKEY_TPL, $email, $subject, $template, $params);
    }

    private static function sendEmail(string $queue_key, $email, $subject, $template, $params = []) {
        //$headers = self::makeHeaders($email, $subject);
        $params['title'] = $subject;

        Traceback::debugLog([
            'send mail',
            $email,
            $subject
        ]);

        $body = self::makeTemplate($template, $params);
        if (App::isProduction()) {
            RedisAdapter::shared()->lPush($queue_key, json_encode([
                'to' => $email,
                'subject' => $subject,
                'body' => $body,
                //'body' => $headers . "\r\n" . $body,
            ]));

            Traceback::debugLog([
                'sent',
                $email,
                $subject,
                '$queue_key: ' . $queue_key,
            ]);
        }

        if (App::isDevelopment()) {
            Mailtrap::instance()
                ->send($email, $subject, $body);
        }
    }
}
