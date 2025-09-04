<?php

namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Logging\LoggerTrait;

/** @package Jk\Vts\Services\AimClipList */
class ClipListMeta {
    use LoggerTrait;
    const metaKey = 'aim-clip-list-items';
    const resourcesKey = 'aim-clip-list-resources';
    public function __construct() {
    }

    public function save(string $postId, array $data, mixed $resources) {
        // error_log(print_r("here: $data", true));
        if ($resources && is_array($resources)) {
            $this->log()->info("saving resources");
            update_post_meta($postId, self::resourcesKey, $resources);
        }
        $this->log()->info("saving items");
        // TODO: make this better when returning errors
        return update_post_meta($postId, self::metaKey, $data);
    }

    public function getItems(int|null $postId) {
        if (!$postId) {
            return [];
        }
        return get_post_meta($postId, self::metaKey, true);
    }

    public function getResources(int|null $postId) {
        if (!$postId) {
            return [];
        }
        return get_post_meta($postId, self::resourcesKey, true) ?? [];
    }

    // TODO: Improve the extendablility of this
    public function validate(array $data) {
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if (!$this->findVimeoId($item) || !isset($item['start']) || !isset($item['end'])) {
                    return [
                        'message' => 'All items must have vimeoId, start, and end keys',
                    ];
                }
                if (!isset($item['clip_id'])) {
                    return [
                        'message' => 'All items must have clip_id',
                    ];
                }
            }
            return true;
        }
        return ['message' => 'Items must be an array'];
    }

    public function createId() {
        return uniqid();
    }

    public function newItem(array $row) {
        $item = [];
        $vimeoId = $this->findVimeoId($row);
        if (!$vimeoId) {
            return new \WP_Error('invalid_vimeo_id', 'Vimeo ID is required', array('status' => 400));
        }
        $item['vimeoId'] = $vimeoId;
        $item['start'] = intval($row['start']);
        $item['end'] = intval($row['end']);
        if (isset($row['summary'])) {
            $item['summary'] = $row['summary'];
        }
        if (isset($row['level'])) {
            $item['level'] = $row['level'];
        }
        if (isset($row['topics'])) {
            $item['topics'] = explode(',', $row['topics']);
        }
        $item['video_type'] = $row['video_type'] ?? 'secondary';
        $item['in_list'] = false;
        $item['clip_id'] = $this->createId();
        return $item;
    }

    public function getDefault() {
        return [
            'vimeoId' => '',
            'start' => 0,
            'end' => 0,
        ];
    }

    public function getResourceSchema() {
        return [
            'items' => [
                'type' => 'object',
                'properties' => [
                    'link' => [
                        'type' => 'string',
                    ],
                    'label' => [
                        'type' => 'string',
                    ],
                    'week_index' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ];
    }

    public function getItemsSchema() {
        return [
            'items' => [
                'type' => 'object',
                'properties' => [
                    'clip_id' => [
                        'type' => 'string',
                    ],
                    'vimeoId' => [
                        'type' => 'string',
                    ],
                    'start' => [
                        'type' => 'integer',
                    ],
                    'end' => [
                        'type' => 'integer',
                    ],
                    'summary' => [
                        'type' => 'string',
                    ],
                    'topics' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'in_list' => [
                        'type' => 'boolean',
                    ],
                    'week_index' => [
                        'type' => 'integer',
                    ],
                    'video_type' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }

    private function findVimeoId(array $row) {
        if (isset($row['vimeoId'])) {
            return $row['vimeoId'];
        }
        if(isset($row['vimeo_id'])){
            return $row['vimeo_id'];
        }
        if(isset($row['vimeo id'])){
            return $row['vimeo id'];
        }
        return null;
    }
}
