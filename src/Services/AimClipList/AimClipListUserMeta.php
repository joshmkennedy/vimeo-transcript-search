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
        return get_user_meta($userId, self::PREFIX . $this->subscribed_lists, true) ?: [];
    }

    private function setSubscribedList($userId, array $list) {
        return update_user_meta($userId, self::PREFIX . $this->subscribed_lists, $list);
    }

    public function addSubscribedList($userId, int $list) {
        $lists = $this->getSubscribedLists($userId) ?: [];
        if (! array_key_exists($list, $lists) || $lists[$list] === false) {
            $lists[$list] = true;
            $this->setSubscribedList($userId, $lists);
        }
        return $lists;
    }

    public function removeSubscribedList(int $userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        if (array_key_exists($list, $lists) && $lists[$list] === true) {
            $lists[$list] = false;
            $this->setSubscribedList($userId, $lists);
        }
        return $lists;
    }

    public function hasSubscribedList(int $userId, int $list) {
        $lists = $this->getSubscribedLists($userId);
        return array_key_exists($list, $lists) && $lists[$list] === true;
    }


    public function getReceivedEmails($userId) {
        return get_user_meta($userId, self::PREFIX . $this->received_emails, true);
    }

    private function setReceivedEmails($userId, array $emails) {
        return update_user_meta($userId, self::PREFIX . $this->received_emails, $emails);
    }

    public function addReceivedEmail(int $userId, int $listId, string $emailName) {
        $emailFullName = $listId . ':' . $emailName;
        $emails = $this->getReceivedEmails($userId);
        if (! array_key_exists($emailFullName, $emails)) {
            $emails[$emailFullName] = date('Y-m-d H:i:s');
            $this->setReceivedEmails($userId, $emails);
        }
        return $emails;
    }

    public function getReceivedEmailsForList(int $userId, int $listId) {
        $emails = $this->getReceivedEmails($userId);
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
