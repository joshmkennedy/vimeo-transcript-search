<?php

namespace Jk\Vts\Services\Email;

use Jk\Vts\Services\Logging\LoggerTrait;

class PreviewClipListEmail {
    use LoggerTrait;
    public EmailTemplate $template;

    /**
     * @param array{
     *     title: string,
     *     content: string,
     *     week_index: string,
     *     videos: array<array{
     *         image_url: string,
     *         vimeoId: string,
     *         title: string
     *         summary: string,
     *         video_type: string,
     *     }>,
     *     resources: array<array{
     *         link: string,
     *         label: string
     *     }>,
     *     clipListId: int
     * } $config
     */
    public function __construct(public array $config) {
        $this->template = new EmailTemplate();
    }

    public function generateClipListEmail($emailAddress) {
        $this->log()->info("generating email content", [$this->config, $emailAddress]);
        $content = $this->template->clipListTemplate(
            site_url() . "/wp-content/uploads/2025/10/AiM-Email-Header.png",
            $this->prepareConfig($this->config, $emailAddress),
        );
        return [
            'emailAddress' => $emailAddress,
            'subject' => $this->config['title'],
            'content' => $content,
            'headers' => [
                'Content-Type: text/html; charset=UTF-8',
            ],
        ];
    }

    private function prepareConfig($config, $emailAddress) {
        $videos = $config['videos'];
        $createLink = fn($clipid) => get_site_url() . "/aim-learning-path/{$this->config['clipListId']}/{$this->config['week_index']}/?clip_id=$clipid";
        $mainVideo = ClipListEmail::mainVideo($videos, $createLink);
        $sideVideos = ClipListEmail::sideVideos($videos, $createLink);
        $links = ClipListEmail::resourceLinks($config['resources']);
        return [
            'title' => $config['title'],
            'emailIntro' => $config['content'],
            'main_video' => $mainVideo,
            'side_videos' => $sideVideos,
            'links' => $links,
            'opt_out_user_link' => ClipListEmail::createOptoutLink(),
        ];
    }
}
