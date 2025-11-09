<?php
namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Logging\LoggerTrait;

/** @package Jk\Vts\Services\AimClipList */
class ClipListMeta {
    use LoggerTrait;
    const metaKey = 'aim-clip-list-items';
    const formId = 'aim-clip-list-form-id';
    const resourcesKey = 'aim-clip-list-resources';
    const weeksInfoKey = 'aim-clip-list-weeks-info';

    public function __construct() {
    }

    public function save(string $postId, array $data, mixed $resources, mixed $weeksInfo, int $formId) {
        // error_log(print_r("here: $data", true));
        if ($resources && is_array($resources)) {
            $this->log()->info("saving resources");
            update_post_meta($postId, self::resourcesKey, $resources);
        }
        if ($weeksInfo && is_array($weeksInfo)) {
            $this->log()->info("saving weeks info");
            update_post_meta($postId, self::weeksInfoKey, $weeksInfo);
        }

        if ($formId) {
            $this->log()->info("saving form id");
            update_post_meta($postId, self::formId, $formId);
        }

        $this->log()->info("saving items");
        // TODO: make this better when returning errors

        return update_post_meta($postId, self::metaKey, $data);
    }

    public function getFormId(int|null $postId) {
        if (!$postId) {
            return 21185;
        }
        $formId = get_post_meta($postId, self::formId, true) ;
        if (!$formId) {
            return 21185;
        }
        return $formId;
    }

    public function setFormId(int|null $postId, string $formId) {
        if (!$postId) {
            return null;
        }
        return update_post_meta($postId, self::formId, $formId);
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

    public function getWeeksForEditor(int|null $postId) {
        $weeks = $this->getWeeksInfo($postId);
        $weeksForEditor = [];
        foreach ($weeks as $week) {
            $weeksForEditor[$week['week_index']] = $week;
        }
        return $weeksForEditor;
    }

    public function normalizeWeeksFromEditor($weeksFromEditor): array {
        $weeks = [];
        foreach ($weeksFromEditor as $weekidx => $weekInfo) {
            $slug = $this->getWeekSlug($weekInfo['week_index'] ?? null);
            if (!$slug) {
                error_log("week_index is missing");
                throw new \Exception("week_index is missing");
            }
            $emails = [];
            foreach ($weekInfo['emails'] as $idx => $email) {
                if (!isset($email['email'])) {
                    error_log("email is missing");
                    throw new \Exception("email name is missing");
                }
                if (!is_string($email['email']) || !str_starts_with($email['email'], $slug)) {
                    error_log("email name is corrupt");
                    throw new \Exception("email name is corrupt, {$email['email']}, should have started with {$slug}");
                }
                if (!isset($email['kind'])) {
                    throw new \Exception("email kind is missing");
                }
                if ($email['kind'] !== 'clipList' && $email['kind'] !== 'textBased') {
                    throw new \Exception("email kind is corrupt, {$email['kind']}");
                }
                if (!isset($email['textContent'])) {
                    error_log("email textContent is missing");
                    throw new \Exception("email textContent is missing");
                }
                if ($email['textContent'] === 'Write the introduction to this weeks videos here!') {
                    error_log("email textContent is was left as the default");
                    throw new \Exception("email textContent is was left as the default, week {$weekidx} email {$idx}");
                }
                if (
                    !isset($email['sendTime'])

                ) {
                    error_log("email is missing sendTime");
                    throw new \Exception("email is missing sendTime");
                }
                if (!is_numeric($email['sendTime'])) {
                    error_log("email sendTime is corrupt");
                    throw new \Exception("email sendTime is corrupt, {$email['sendTime']}");
                }

                $weekIndexNum = str_replace('week_', '', $weekidx);
                if ($idx < count($weekInfo['emails']) - 1) {
                    $nextEmail = $weekInfo['emails'][$idx + 1]['email'];
                } elseif ($weekIndexNum < count($weeksFromEditor) && isset($weeksFromEditor['week_' . $weekIndexNum + 1])) {
                    $nextWeek = $weeksFromEditor['week_' . $weekIndexNum + 1];
                    $nextEmail = $nextWeek['emails'][0]['email'];
                } else {
                    $nextEmail = 'last';
                }
                $email['next_email'] = $nextEmail;

                $emails[] = $email;
            }
            if (
                count($weeks) &&
                $weeks[count($weeks) - 1]['emails'][count($weeks[count($weeks) - 1]['emails']) - 1]['next_email'] === null &&
                count($weekInfo['emails'])
            ) {
                $weeks[count($weeks) - 1]['emails'][count($weeks[count($weeks) - 1]['emails']) - 1]['next_email'] = $weekInfo['emails'][0]['email'];
            }

            $weeks[] = [
                'week_index' => $weekInfo['week_index'],
                'emails' => $emails,
            ];
            unset($emails);
        }
        return $weeks;
    }


    public function getWeeksInfo(int|null $postId) {
        if (!$postId) {
            return $this->getWeeksInfoDefaults();
        }
        $meta = get_post_meta($postId, self::weeksInfoKey, true);
        return is_array($meta) ? $meta : $this->getWeeksInfoDefaults();
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
                                'kind' => [
                                    'type' => 'string', // cliplist or text_based
                                ],
                                'textContent' => [
                                    'type' => 'string', // text content for the email
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
                'kind' => 'clipList',
                'textContent' => 'Write the introduction to this weeks videos here',
                'sendTime' => '1', // send on the following Monday,
                'next_email' => 'week_' . $weekIndex . '_reminders',
            ],
            // [
            //     'email' => 'week_' . $weekIndex . '_reminders',
            //     'kind' => 'textBased',
            //     'textContent' => 'Write the reminders for this weeks videos here',
            //     'sendTime' => '3', // send on the following Wednesday,
            //     'next_email' => $nextEmail,
            // ],
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

    // TODO use $this->getEmailInfo
    public function getNextEmail(int $listId, string $emailName) {
        $emailInfo = $this->getEmailInfo($listId, $emailName);
        if (!$emailInfo) {
            return null;
        }
        $nextEmail = $emailInfo['next_email'];

        return $nextEmail;
    }

    public function getEmailInfo(int $listId, string $emailName) {
        $weekIdx = $this->getWeekSlug($emailName);
        if (!$weekIdx) {
            $this->log()->info("No week index found for email name $emailName");
            return null;
        }
        $clipListInfo = $this->getWeeksInfo($listId);
        $weekInfo = array_find($clipListInfo, fn($week) => $week['week_index'] === $weekIdx);
        if (!$weekInfo) {
            $this->log()->info("No week info found for user $listId:$weekIdx");
        }
        $email = array_find($weekInfo['emails'], fn($email) => $email['email'] === $emailName);
        if (!$email) {
            $this->log()->info("No email found for user $listId:$emailName");
        }
        return $email;
    }

    public function getWeekSlug(string $emailName) {
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
    public function getWeekIdx(string $emailName) {
        $weekSlug = $this->getWeekSlug($emailName);
        if (!$weekSlug) {
            return null;
        }
        return (int)str_replace('week_', '', $weekSlug);
    }
}
