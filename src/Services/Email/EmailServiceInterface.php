<?php

namespace Jk\Vts\Services\Email;

interface EmailServiceInterface {
    public function send($email, $subject, $content, $headers);
}
