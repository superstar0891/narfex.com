<?php


namespace Core\Services\Mail;


use PHPMailer\PHPMailer\PHPMailer;

class Mailtrap {
    private static $_instance = null;

    private $mailer;

    private function __construct() {
        $php_mailer = new PHPMailer();
        $php_mailer->isSMTP();
        $php_mailer->Host = 'smtp.mailtrap.io';
        $php_mailer->SMTPAuth = true;
        $php_mailer->Username = '2ae7b65b173fbe';
        $php_mailer->Password = '156a2421ceffd1';
        $php_mailer->SMTPSecure = 'tls';
        $php_mailer->Port = 2525;
        $php_mailer->setFrom('test@narfex.dev', 'Narfex');
        $php_mailer->Encoding = PHPMailer::ENCODING_BASE64;
        $php_mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mailer = $php_mailer;
    }

    public static function instance() {
        if (!self::$_instance) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public function send(string $email, string $subject, string $content) {
        $this->mailer->addAddress($email);
        $this->mailer->Subject = $subject;
        $this->mailer->isHTML();
        $this->mailer->Body = $content;
        if (!$this->mailer->send()) {
            throw new \Exception($this->mailer->ErrorInfo);
        }
    }

    private function __wakeup() {}
}
