<?php

namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Logging\LoggerTrait;

/** @package Jk\Vts\Services\AimClipList */
class ClipListMeta {
    use LoggerTrait;
    const metaKey = 'aim-clip-list-items';
    const resourcesKey = 'aim-clip-list-resources';
    const weeksInfoKey = 'aim-clip-list-weeks-info';

    public function __construct() {
    }

    public function save(string $postId, array $data, mixed $resources, mixed $weeksInfo) {
        // error_log(print_r("here: $data", true));
        if ($resources && is_array($resources)) {
            $this->log()->info("saving resources");
            update_post_meta($postId, self::resourcesKey, $resources);
        }
        if ($weeksInfo && is_array($weeksInfo)) {
            $this->log()->info("saving weeks info");
            update_post_meta($postId, self::weeksInfoKey, $weeksInfo);
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

    public function getWeeksInfo(int|null $postId) {
        if (!$postId) {
            return [];
        }
        return get_post_meta($postId, self::weeksInfoKey, true) ?? $this->getWeeksInfoDefaults();
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

    public function getWeeksInfoSchema() {
        return [
            'items' => [
                'type' => 'object',
                'properties' => [
                    'week_index' => [
                        'type' => 'string', // week_1, week_2, week_3
                    ],
                    'emails' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'email' => [
                                    'type' => 'string', // email name (week_1_videos_for_this_week, week_1_reminders)
                                ],
                                'sendTime' => [
                                    'type' => 'string', // day of week ( 1, 2 ,3...) 0=Sunday, 1=Monday
                                ],
                                'next_email' => [
                                    'type' => 'string', // week_2_videos_for_this_week, week_2_reminders, last (last if there are no more emails or weeks configured)
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getWeeksInfoDefaults() {
        return [
            [
                'week_index' => 'week_1',
                'emails' => $this->getWeekDefaultEmails(weekIndex: 1, nextEmail: 'last'),
            ],
        ];
    }

    public function getWeekDefaultEmails(int $weekIndex, string $nextEmail = 'last') {
        $emails = [
            [
                'email' => 'week_' . $weekIndex . '_videos_for_this_week',
                'sendTime' => '1', // send on the following Monday,
                'next_email' => 'week_' . $weekIndex . '_reminders',
            ],
            [
                'email' => 'week_' . $weekIndex . '_reminders',
                'sendTime' => '3', // send on the following Wednesday,
                'next_email' => $nextEmail,
            ],
        ];
        return $emails;
    }

    public function addEmailToWeek(int $listId, string $emailName, int $weekIndex, string $nextEmail) {
        throw new \Exception("Not implemented");
    }

    private function findVimeoId(array $row) {
        if (isset($row['vimeoId'])) {
            return $row['vimeoId'];
        }
        if (isset($row['vimeo_id'])) {
            return $row['vimeo_id'];
        }
        if (isset($row['vimeo id'])) {
            return $row['vimeo id'];
        }
        return null;
    }

    public function getNextEmail(int $listId, string $emailName) {
        $weekIdx = $this->getWeekIdx($emailName);
        if (!$weekIdx) {
            return null;
        }
        $weekInfo = $this->getWeeksInfo($listId);

        $key = array_search($weekIdx, $weekInfo);
        if (!isset($weekInfo[$key]) || !isset($weekInfo[$key]['emails'])) {
            return null;
        }

        $email = array_find($weekInfo[$key]['emails'], fn($email) => $email['email'] === $emailName);
        if (!$email) {
            return null;
        }
        $nextEmail = $email['next_email'];

        return $nextEmail;
    }

    private function getWeekIdx(string $emailName) {
        if (strpos($emailName, 'week_') !== 0) {
            error_log("Email name $emailName does not start with week_");
            return null;
        }
        preg_match('/(week_\d+)/', $emailName, $matches);
        if (!isset($matches[1]) || $matches[1] === '') {
            error_log("No week index found for email name $emailName");
            return null;
        }
        return $matches[1];
    }
}
