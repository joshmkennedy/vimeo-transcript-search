<?php

namespace Jk\Vts\Services;


class VimeoApi {
    const API_BASE_URL = "https://api.vimeo.com";
    private string $apiKey;

    public function __construct() {
        $this->apiKey = get_option('jp_vimeo_api_key');
    }

    public  function getId(string $url): string|null {
        $vimeoId = null;
        if (preg_match('/vimeo\.com\/([0-9]+)/', $url, $matches)) {
            $vimeoId = $matches[1];
        }
        return $vimeoId;
    }

    public function getVideoInfo(string $vimeoId, $fields = ['name', 'uri']): array {
        $vimeoResponse = self::vimeoApiGet("/videos/$vimeoId");
        if (empty($vimeoResponse)) {
            return [];
        }
        $response = [];
        foreach ($fields as $key => $field) {
            if(is_int($key)){
                $key = $field;
            }
            if(is_string($key) && $key == $field && isset($vimeoResponse[$key])){
                $response[$key] = $vimeoResponse[$key];
                continue;
            }
            if(is_array($field) && isset($vimeoResponse[$key])){
                $response[$key] = [];
                foreach($field as $subKey){
                    if(isset($vimeoResponse[$key][$subKey])){
                        $response[$key][$subKey] = $vimeoResponse[$key][$subKey];
                    }
                }
            }
        }
        return $response;
    }



    public function vimeoApiGet(string $path): array {
        if (!$this->apiKey) {
            throw new \Exception("No api key");
        }
        $url = self::API_BASE_URL . $path;
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
        ];
        $response = wp_remote_get($url, [
            'headers' => $headers,
        ]);
        if (is_wp_error($response)) {
            error_log("error getting vimeo api");
            return [];
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getThumb(string $id, null|string $fallback = null): string {
        if (!$fallback) {
            $fallback = "https://placehold.co/600x400";
        }
        if (get_transient("jp_vimeo_thumb_$id")) {
            return get_transient("jp_vimeo_thumb_$id");
        }
        $vimeoResponse = self::vimeoApiGet("/videos/$id/pictures");
        if (!isset($vimeoResponse['data'])) {
            return $fallback;
        }
        $thumbnails = $vimeoResponse['data'][0]['sizes'];
        $thumbnail = array_filter($thumbnails, fn($arg) => $arg['width'] > 600);
        $thumbnail = array_shift($thumbnail);
        if (!$thumbnail) {
            return $fallback;
        }
        $thumbnailUrl = $thumbnail['link'];
        set_transient("jp_vimeo_thumb_$id", $thumbnailUrl, \MONTH_IN_SECONDS);
        return $thumbnailUrl;
    }
}
