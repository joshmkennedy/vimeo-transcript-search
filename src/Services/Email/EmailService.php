<?php

namespace Jk\Vts\Services\Email;


class EmailService implements EmailServiceInterface {
    public function __construct() {
    }

    public function send($email, $subject, $content, $headers) {
        $res = wp_mail($email, $subject, $content, $headers);
        if (!$res) {
            error_log("Failed to send email", print_r([
                'email' => $email,
                'subject' => $subject,
                'content' => $content,
                'headers' => $headers,
            ], true));
        }
    }
}
