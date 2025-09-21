<?php

namespace Jk\Vts\Services;

class AiSummarize {
    private Chat $chat;
    public function __construct() {
        $this->chat = new Chat();
    }
    public function summarizeVideo(string $summary, string $keyPoints) {
        $messages = $this->videoPrompt($summary, $keyPoints);
        error_log(print_r($messages, true));

        return $this->chat->freeForm__openai($messages, model: 'gpt-5-mini-2025-08-07');
    }
    public function summarizeEmail(string $joinedSummaries) {
        $messages = $this->emailPrompt($joinedSummaries);
        return $this->chat->freeForm__openai($messages, model: 'gpt-5-mini-2025-08-07');
    }

    private function videoPrompt(string $summary, string $keyPoints) {
        $system = implode("\n", [
            "You are a helpful video summarizer. You will take in a video summary and key points and topics and then summarize the video.",
            "You will also summarize the video in a way that is easy to read and understand.",
            "You will also summarize the video so that the user will want to watch it, and think there is something interesting in the video.",
            "Your summary will be short and to the point. Should be scanable in less than 5 seconds.",
            "Only return the summary, nothing else.",
        ]);
        $prompt = implode("\n", [
            "Video Summary: $summary",
            "Key Points: $keyPoints",
        ]);
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];
    }

    private function emailPrompt(string $joinedSummaries) {
        $system = implode("\n", [
            "You are a genius email marketer. You will be given a list of summaries that will be summaries of videos that will be included in this weeks email, of videos for the user to watch",
            "You will pick out the over all theme and then something that is interesting and will make the user want to watch the videos.",
            "You will also summarize the email in a way that is easy to read and understand.",
            "Only return the summary, nothing else.",
        ]);
        $prompt = implode("\n", [
            "Summaries: $joinedSummaries",
        ]);
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];
    }
}
