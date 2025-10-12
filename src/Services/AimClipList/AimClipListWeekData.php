<?php
/**
```php
$aclWeek = new AimClipListWeekData(
    clipListId: int,
    weekIndex: int,
);

wp_localize_script(
    'acl-vimeo-plugin',
    'aclWeek',
    $aclWeek->getVimeoPluginData(),
);

```
**/

namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Logging\LoggerTrait;
use Jk\Vts\Services\VimeoInfoVideoList;

class AimClipListWeekData {

    use LoggerTrait;

    private ClipListMeta $meta;

    public function __construct(public int $clipListId, public int $weekIndex) {
        $this->meta = new ClipListMeta();
    }

    /**
     * @return array{
     *     videos: array<array{
     *         vimeoId:string,
     *         start:int,
     *         end:int,
     *         clipId: string,
     *         summary:string,
     *         name:string,
     *         image_url:string,
     *         }>,
     *      resources: array<array{label:string, link:string}>,
     *      weekIndex:string,
     *      clipListId:string,
     *      selectedVideo:string,
     *  }
     **/
    public function getVimeoPluginData():array {
        $introText = $this->meta->getEmailInfo($this->clipListId, 'week_' . $this->weekIndex . '_videos_for_this_week');

        $items = $this->meta->getItems($this->clipListId);
        $items = collect($items)->reject(fn($item) => !isset($item['week_index']) || $item['week_index'] !== $this->weekIndex)->toArray();
        $videos = collect(VimeoInfoVideoList::getVideoInfoList($items));
        $selectedVideo = ($videos->first(fn($video)=>$video['video_type'] === 'lecture') ?? $videos->first())['clip_id'];
        $resources = collect($this->meta->getResources($this->clipListId))->reject(fn($item) => $item['week_index'] !== $this->weekIndex);
        return [
            'intro' => $introText['textContent'],
            'videos' => $videos->map(fn($video) => [
                'vimeoId' => $video['vimeoId'],
                'start' => $video['start'],
                'end' => $video['end'],
                'clipId' => $video['clip_id'],
                'summary' => $video['summary'],
                'name' => $video['name'],
                'image_url' => $video['pictures']['base_link'],
            ])->values()->toArray(),
            'resources' => $resources->map(fn($resource) => [
                'label' => $resource['label'],
                'link' => $resource['link'],
            ])->values()->toArray(),
            'weekIndex' => $this->weekIndex,
            'clipListId' => $this->clipListId,
            'selectedVideo' => $selectedVideo,
        ];
    }
}
