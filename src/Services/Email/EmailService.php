<?php

namespace Jk\Vts\Services\Email;


class EmailService implements EmailServiceInterface {
    public function __construct() {
    }

    public function send($email, $subject, $content, $headers) {
        wp_mail($email, $subject, $content, $headers);
    }
}
