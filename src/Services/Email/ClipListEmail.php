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
        $this->config['opt_out_user_link'] = $this->createOptoutLink();
        $content = $this->template->clipListTemplate(
            site_url() . "/wp-content/uploads/2025/10/AiM-Email-Header.png",
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
        $this->config['opt_out_user_link'] = $this->createOptoutLink();
        $content = $this->template->textBasedTemplate(
            site_url() . "/wp-content/uploads/2025/10/AiM-Email-Header.png",
            $this->config,
        );
        return $content;
    }

    public function getWeekConfig($weekIndex):array {
        $emailIntro = $this->meta->getEmailInfo($this->clipListId,  $weekIndex . '_videos_for_this_week')['textContent'];
        $items = $this->meta->getItems($this->clipListId);
        $resources = $this->meta->getResources($this->clipListId);

        $wkVideos = collect($items)
            ->filter(fn($item) => isset($item['week_index']) && "week_" . (string)$item['week_index'] == $weekIndex)
            ->toArray();
        $wkResources = collect($resources)
            ->filter(fn($item) => isset($item['week_index']) && "week_" . (string)$item['week_index'] == $weekIndex)
            ->toArray();

        $videos = collect(VimeoInfoVideoList::getVideoInfoList($wkVideos))
            ->toArray();
        $createLink = fn($clipid) => get_site_url() . "/aim-learning-path/{$this->clipListId}/$weekIndex?clip_id=$clipid";
        $mainVideo = self::mainVideo($videos, $createLink);
        $sideVideos = self::sideVideos($videos, $createLink);

        $links = self::resourceLinks($wkResources);

        $emailTitle = "This weeks videos and resources";

        return [
            'title' => $emailTitle,
            'emailIntro' => $emailIntro,
            'main_video' => $mainVideo ? $mainVideo : [],
            'side_videos' => $sideVideos ? $sideVideos : [],
            'links' => $links,
            'opt_out_user_link' => "",
        ];
    }

    public static function createOptoutLink() {
        return site_url("aim-learning-path-settings");
    }

    public static function sideVideos($items, $createLink) {
        $sides = collect($items)->filter(fn($item) => isset($item['video_type']) && ($item['video_type'] == 'secondary-lecture' || $item['video_type'] == 'lab'));
        return $sides->map(fn($v) => self::fmtVideoConfig($v, $createLink))->toArray();
    }

    public static function mainVideo($items, $createLink) {
        if (count($items) == 0) {
            return null;
        }
        $main = array_find($items, fn($item) => isset($item['video_type']) && ($item['video_type'] == 'lecture'));
        if (!$main) {
            // throw new \Exception("No main video found");
            $main = $items[0];
        }

        return self::fmtVideoConfig($main, $createLink);
    }

    public static function linkToClipListPage($weekIndex, $id, $kind) {
        // todo: pull in from site option
        return get_site_url() . "/aim-learning-path/$weekIndex?id=$id&kind=$kind";
    }

    public static function resourceLinks($items) {
        return collect($items)
            ->map(fn($item) => [
                'link' => $item['link'],
                'text' => $item['label'],
            ])
            ->toArray();
    }

    private static function fmtVideoConfig($item, $createLink) {
        // TODO: need to override the link because we dont want tolink to the player but the cliplist page.
        return [
            'link' => isset($item['link']) ? $item['link'] : ($createLink($item['clip_id'])),
            'title' => isset($item['title']) ? $item['title'] : $item['name'],
            'summary' => isset($item['summary']) ? $item['summary'] : "",
            'image_url' => isset($item['image_url']) ? $item['image_url'] : $item['pictures']['base_link'],
        ];
    }
}
