<?php

namespace Jk\Vts\Services\AimClipList;

class AimClipListUserMeta {
    const PREFIX = 'aim_clip_list_';
    public string $subscribed_lists = "subscribed_lists";
    public string $received_emails = "received_emails";
    public string $next_email = "next_email";

    public function __construct() {
    }

    public function getSubscribedLists($userId) {
        $meta = get_user_meta($userId, self::PREFIX . $this->subscribed_lists, true);
        if (!is_array($meta)) {
            return [];
        }
        // For now, return as-is (legacy bools or new arrays); post-migration, all will be arrays
        // Optional: Log if mixed types detected
        foreach ($meta as $listId => $value) {
            if (is_bool($value)) {
                error_log("Legacy bool subscription detected for user $userId, list $listId; migrate soon.");
            }
        }
        return $meta;
    }
    public function getSubscriptionStatus(int $userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        if (!isset($lists[$list])) {
            return 'not_subscribed';
        }
        $value = $lists[$list];
        if (is_bool($value)) {
            return $value ? 'active' : 'inactive';
        }

        if (isset($value['subscribed_on']) && (!isset($value['finished_on']) || $value['finished_on'] === null)) {
            return 'active';
        } else {
            return 'inactive';
        }
    }

    public function getSubscriptionDate(int $userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        if (!isset($lists[$list])) {
            return null;
        }
        $value = $lists[$list];
        if (is_bool($value)) {
            if ($value === true) {
                // Fallback to earliest email or current time
                $emails = $this->getReceivedEmailsForList($userId, $list);
                if (!empty($emails)) {
                    $earliest = min(array_map('strtotime', array_values($emails)));
                    return $earliest;
                }
                return time();
            }
            return null;
        }
        return $value['subscribed_on'] ?? null;
    }


    private function setSubscribedList($userId, array $list) {
        // Optional: Validate structure
        foreach ($list as $listId => $data) {
            if (!is_array($data) || !isset($data['subscribed_on'])) {
                throw new \InvalidArgumentException("Invalid subscription structure for list $listId");
            }
        }
        return update_user_meta($userId, self::PREFIX . $this->subscribed_lists, $list);
    }

    public function addSubscribedList($userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        $isActive = $this->getSubscriptionStatus($userId, $list) === 'active';
        if (!$isActive) {
            $lists[$list] = ['subscribed_on' => time(), 'finished_on' => null];
            $this->setSubscribedList($userId, $lists);
        }
        return $lists;
    }

    public function removeSubscribedList(int $userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        if (isset($lists[$list])) {
            $isActive = is_bool($lists[$list]) ? $lists[$list] : (isset($lists[$list]['finished_on']) && $lists[$list]['finished_on'] === null);
            if ($isActive) {
                if (is_bool($lists[$list]) && $lists[$list] === true) {
                    $lists[$list] = ['subscribed_on' => time(), 'finished_on' => time()];
                } else {
                    $lists[$list]['finished_on'] = time();
                }
                $this->setSubscribedList($userId, $lists);
            }
        }
        return $lists;
    }

    public function hasSubscribedList(int $userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        if (!isset($lists[$list])) {
            return false;
        }
        $value = $lists[$list];
        if (is_bool($value)) {
            return $value === true;
        }
        return isset($value['finished_on']) && $value['finished_on'] === null;
    }


    public function getReceivedEmails($userId) {
        return (array)get_user_meta($userId, self::PREFIX . $this->received_emails, true);
    }

    private function setReceivedEmails($userId, array $emails) {
        return update_user_meta($userId, self::PREFIX . $this->received_emails, $emails);
    }

    public function addReceivedEmail(int $userId, int $listId, string $emailName) {
        $emailFullName = $listId . ':' . $emailName;
        $emails = $this->getReceivedEmails($userId);
        if (!is_array($emails) || ! array_key_exists($emailFullName, $emails)) {
            $emails[$emailFullName] = date('Y-m-d H:i:s');
            $this->setReceivedEmails($userId, $emails);
        }
        return $emails;
    }

    public function getReceivedEmailsForList(int $userId, int $listId) {
        $emails = (array)$this->getReceivedEmails($userId);
        $emailsForList = [];
        foreach ($emails as $emailFullName => $date) {
            if (strpos($emailFullName, $listId . ':') === 0) {
                $email = substr($emailFullName, strlen($listId . ':'));
                $emailsForList[$email] = $date;
            }
        }
        return $emailsForList;
    }

    public function removeAllReceivedEmailsFromList($userId, $listId) {
        $emails = $this->getReceivedEmails($userId);
        $updatedEmailsRecieved = [];
        foreach ($emails as $emailFullName => $date) {
            if (strpos($emailFullName, $listId . ':') !== 0) {
                $updatedEmailsRecieved[$emailFullName] = $date;
            }
        }
        $this->setReceivedEmails($userId, $updatedEmailsRecieved);
        return $updatedEmailsRecieved;
    }

    public function getLastEmailSentForList(int $userId, int $listId) {
        $emails = $this->getReceivedEmailsForList($userId, $listId);
        if (count($emails) > 0) {
            usort($emails, function ($a, $b) {
                return strtotime($b) - strtotime($a);
            });
            return [array_keys($emails)[0], array_values($emails)[0]];
        }
        return null;
    }

    public function getNextEmailKey(int $listId) {
        return self::PREFIX . $this->next_email . "_" . $listId;
    }

    public function deleteNextEmailForList(int $userId, int $listId) {
        delete_user_meta($userId, $this->getNextEmailKey($listId));
    }

    public function setNextEmailForList(int $userId, int $listId, string $emailName) {
        update_user_meta($userId, $this->getNextEmailKey($listId), $emailName);
    }
}
