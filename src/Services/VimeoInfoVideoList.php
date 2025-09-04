<?php

namespace Jk\Vts\Services;

class VimeoInfoVideoList {
    public static function getVideoInfoList(array $videos): array {
        $vimeoApi = new \Jk\Vts\Services\VimeoApi();
        foreach ($videos as $key => $video) {
            $vimeoId = $video['vimeoId'] ?? null;
            if ($vimeoId) {
                if ($cached = get_transient("vts_vimeo_info_$vimeoId")) {
                    $videos[$key] = array_merge($cached, $video);
                    continue;
                }
                $updatedCache = $vimeoApi->getVideoInfo($vimeoId, ['name', 'uri', 'pictures' => [0 => 'base_link'], 'player_embed_url']);
                set_transient("vts_vimeo_info_$vimeoId", $updatedCache, \YEAR_IN_SECONDS);

                $video[$key] = array_merge($video, $updatedCache);
            }
        }
        return $videos;
    }

    /**
     Instead of of a item for each video,
     we can just return a set of all the videos
     less items and only the name, ui, pictures, and player_embed_url
     one item per unique vimeoId
    **/
    public static function getVideoInfoSet(array $videos):array {
        $vimeoApi = new \Jk\Vts\Services\VimeoApi();
        $videoInfo = [];
        foreach ($videos as $video) {
            $vimeoId = $video['vimeoId'] ?? null;
            if ($vimeoId && !array_key_exists($vimeoId, $videoInfo)) {
                if ($cached = get_transient("vts_vimeo_info_$vimeoId")) {
                    $videoInfo[$vimeoId] = $cached;
                    continue;
                }
                $updatedCache = $vimeoApi->getVideoInfo($vimeoId, ['name', 'uri', 'pictures' => [0 => 'base_link'], 'player_embed_url']);
                set_transient("vts_vimeo_info_$vimeoId", $updatedCache, \YEAR_IN_SECONDS);
                $videoInfo[$vimeoId] = $updatedCache;
            }
        }
        return $videoInfo;
    }
}
