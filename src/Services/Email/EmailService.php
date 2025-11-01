<?php

namespace Jk\Vts\Services\Email;


class EmailService implements EmailServiceInterface {
    public function __construct() {
    }

    public function send($email, $subject, $content, $headers) {
        $res = wp_mail($email, $subject, $content, $headers);
        if (!$res) {
            \Sentry\captureException(new \Exception("Failed to send email: " . print_r([
                'email' => $email,
                'subject' => $subject,
                'headers' => $headers,
            ], true)));
            error_log("Failed to send email: " . print_r([
                'email' => $email,
                'subject' => $subject,
                'headers' => $headers,
            ], true));
        }
    }
}
