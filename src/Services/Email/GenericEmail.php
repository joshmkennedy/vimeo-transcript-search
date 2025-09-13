<?php

namespace Jk\Vts\Services\Email;

class GenericEmail {
    public EmailTemplate $template;
    public array $config;
    public EmailServiceInterface $emailService;
    public function __construct() {
        $this->template = new EmailTemplate();
        $this->emailService = new EmailService();
    }

    public function sendEmail($emailAddress, $title, $content) {
        $this->config['title'] = $title;
        $this->config['textContent'] = $content;

        $content = $this->template->textBasedTemplate(
            site_url() . "/wp-content/uploads/2025/01/1-Mark@2x.png",
            $this->config,
        );
        $this->emailService->send(
            $emailAddress,
            $this->config['title'],
            $content,
            [
                'Content-Type: text/html; charset=UTF-8',
            ],
        );
    }


}
