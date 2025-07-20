<?php

namespace Jk\Vts\Admin;

class Settings {
    public function register() {
        register_setting('general', 'openai-api-key');
        register_setting('general', 'vts-turso-url');
        register_setting('general', 'vts-turso-key');
        register_setting('general', 'reranker-api-key');

        add_settings_section(
            'vts-settings',
            'Vimeo Transcript Search Settings',
            [$this, 'renderSection'],
            'general'
        );

        add_settings_field(
            'openai-api-key',
            'OpenAI API Key',
            [$this, 'renderField'],
            'general',
            'vts-settings'
        );

        add_settings_field(
            'vts-turso-url',
            'Turso URL',
            [$this, 'renderFieldTursoUrl'],
            'general',
            'vts-settings'
        );
        add_settings_field(
            'vts-turso-key',
            'Turso Key',
            [$this, 'renderFieldTurso'],
            'general',
            'vts-settings'
        );
        add_settings_field(
            'reranker-api-key',
            'Reranker API Key',
            [$this, 'renderRerankerApiKey'],
            'general',
            'vts-settings'
        );
    }


    public function renderSection() {
        echo '<p>Settings for the Vimeo Transcript Search plugin.</p>';
    }

    public function renderField() {
        $apiKey = get_option('openai-api-key');
        echo '<input type="password" name="openai-api-key" value="' . $apiKey . '">';
    }
    public function renderFieldTursoUrl() {
        $apiKey = get_option('vts-turso-url');
        echo '<input type="text" name="vts-turso-url" value="' . $apiKey . '">';
    }
    public function renderFieldTurso() {
        $apiKey = get_option('vts-turso-key');
        echo '<input type="password" name="vts-turso-key" value="' . $apiKey . '">';
    }
    public function renderRerankerApiKey() {
        $apiKey = get_option('reranker-api-key');
        echo '<input type="password" name="reranker-api-key" value="' . $apiKey . '">';
    }
}
