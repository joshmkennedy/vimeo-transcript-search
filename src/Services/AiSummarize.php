<?php

namespace Jk\Vts\Services;

class AiSummarize {
    private Chat $chat;
    public function __construct() {
        $this->chat = new Chat();
    }
    public function summarizeVideo(string $summary, string $keyPoints) {
        $messages = $this->videoPrompt($summary, $keyPoints);

        return $this->chat->freeForm__openai($messages, model: 'gpt-5-mini-2025-08-07');
    }
    public function summarizeEmail(string $joinedSummaries) {
        $messages = $this->emailPrompt($joinedSummaries);
        return $this->chat->freeForm__openai($messages, model: 'gpt-5-mini-2025-08-07');
    }

    private function videoPrompt(string $summary, string $keyPoints) {

        $system = implode("\n", [
            "You are a genius email marketer. You will be given a list of summaries that will be summaries of videos that will be included in this weeks email, of videos for the user to watch",
            "You will pick out the over all theme and then something that is interesting and will make the user want to watch the videos.",
            "You will also summarize the video in a way that is easy to read and understand.",
            "Only return the summary, nothing else. Only write one sentence, max 10 - 15 words.",
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
        $system = "You are a genius email marketer crafting engaging prose for a weekly curated video newsletter. You'll receive
summaries of 3-5 videos on related topics. Identify the overarching theme, then give a catchy theme intro sentence that tells the user why this content has been selected.
Format:
<strong>Topic</strong>
Catchy intro sentence
";
        $prompt = implode("\n", [
            "Summaries: $joinedSummaries",
        ]);
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];
    }
}
