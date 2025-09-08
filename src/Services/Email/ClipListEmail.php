<?php

namespace Jk\Vts\Services\Email;

use Jk\Vts\Services\AimClipList\ClipListMeta;
use Jk\Vts\Services\VimeoInfoVideoList;

/** @package Jk\Vts\Services\Email */
class ClipListEmail {
    public EmailTemplate $template;
    public ClipListMeta $meta;
    public array $config;
    public EmailServiceInterface $emailService;
    public function __construct(private int $clipListId, $weekIndex) {
        $this->template = new EmailTemplate();
        $this->meta = new ClipListMeta();
        $this->config = $this->getWeekConfig($weekIndex);
        $this->emailService = new EmailService();
    }

    public function send($emailAddress) {
        $this->config['opt_out_user_link'] = $this->createOptoutLink($emailAddress);
        $content = $this->template->clipListTemplate(
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


    public function generateClipListEmail($emailAddress) {
        $content = $this->generateClipListEmailContent($emailAddress);
        return [
            'emailAddress' => $emailAddress,
            'subject' => $this->config['title'],
            'content' => $content,
            'headers' => [
                'Content-Type: text/html; charset=UTF-8',
            ],
        ];
    }

    private function generateClipListEmailContent($emailAddress) {
        $this->config['opt_out_user_link'] = $this->createOptoutLink($emailAddress);
        $content = $this->template->clipListTemplate(
            site_url() . "/wp-content/uploads/2025/01/1-Mark@2x.png",
            $this->config,
        );
        return $content;
    }

    public function generateTextBasedEmail($emailAddress) {
        $content = $this->generateTextBasedEmailContent($emailAddress);
        return [
            'emailAddress' => $emailAddress,
            'subject' => $this->config['title'],
            'content' => $content,
            'headers' => [
                'Content-Type: text/html; charset=UTF-8',
            ],
        ];
    }
    private function generateTextBasedEmailContent($emailAddress) {
        $this->config['opt_out_user_link'] = $this->createOptoutLink($emailAddress);
        $content = $this->template->textBasedTemplate(
            site_url() . "/wp-content/uploads/2025/01/1-Mark@2x.png",
            $this->config,
        );
        return $content;
    }

    public function getWeekConfig($weekIndex) {
        $items = $this->meta->getItems($this->clipListId);
        $resources = $this->meta->getResources($this->clipListId);

        $wkVideos = collect($items)
            ->filter(fn($item) => isset($item['week_index']) && (string)$item['week_index'] == $weekIndex)
            ->toArray();
        $wkResources = collect($resources)
            ->filter(fn($item) => isset($item['week_index']) && (string)$item['week_index'] == $weekIndex)
            ->toArray();

        $videos = collect(VimeoInfoVideoList::getVideoInfoList($wkVideos))
            ->toArray();
        $mainVideo = $this->mainVideo($videos);
        $sideVideos = $this->sideVideos($videos);

        $links = collect($wkResources)
            ->map(fn($item) => [
                'link' => $item['link'],
                'text' => $item['label'],
            ])
            ->toArray();

        $emailTitle = "This weeks videos and resources";

        return [
            'title' => $emailTitle,
            'main_video' => $mainVideo,
            'side_videos' => $sideVideos,
            'links' => $links,
            'opt_out_user_link' => "",
        ];
    }

    public function createOptoutLink($emailAddress) {
        return get_rest_url('vts/v1/vts/opt-out-user') . "?email=$emailAddress&clip_list_id=$this->clipListId";
    }

    private function sideVideos($items) {
        $sides = collect($items)->filter(fn($item) => isset($item['video_type']) && ($item['video_type'] == 'secondary' || $item['video_type'] == 'lab'));
        return $sides->map(fn($v) => $this->fmtVideoConfig($v))->toArray();
    }

    private function mainVideo($items) {
        print_r($items);
        $main = array_find($items, fn($item) => isset($item['video_type']) && ($item['video_type'] == 'lecture' || $item['video_type'] == 'main'));
        if (!$main) {
            throw new \Exception("No main video found");
        }
        return $this->fmtVideoConfig($main);
    }


    private function fmtVideoConfig($item) {
        return [
            'link' => $item['player_embed_url'],
            'title' => $item['name'],
            'image_url' => $item['pictures']['base_link'],
        ];
    }
}
