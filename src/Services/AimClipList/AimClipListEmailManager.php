<?php

namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Email\ClipListEmail;
use Jk\Vts\Services\Email\EmailService;
use Jk\Vts\Services\Email\EmailServiceInterface;
use Jk\Vts\Services\ScheduledJobs;

class AimClipListEmailManager {
    const SEND_QUEUED_EMAILS_ACTION = 'send_queued_email';
    private ScheduledJobs $scheduledJobs;
    private ClipListMeta $meta;
    private AimClipListUserMeta $userMeta;
    private EmailServiceInterface $emailService;
    private array $cache;
    public function __construct(
        public string $path,
        public string $url,
    ) {
        $this->scheduledJobs = new ScheduledJobs();
        $this->meta = new ClipListMeta();
        $this->cache = [];
        $this->emailService = new EmailService();
        $this->userMeta = new AimClipListUserMeta();
    }

    public function generateEmailContent(int $listId, string $emailName) {
        return new ClipListEmail($listId, $emailName);
    }

    public function queueEmails() {
        // get all users who have subscribed to a list
        $users = $this->getSubscribedUsers();

        $config = $this->buildCampaignConfig($users);

        // loop through each list id and weekid and cache the email that needs to be sent.
        foreach ($config as $listId => $emails) {
            foreach ($emails as $emailName => $users) {
                // @see the getSubscribedUsers method for what the user object looks like
                foreach ($users as $userId => $user) { 
                    $user = (array)$user;
                    $emailAddress = $user['user_email'];
                    if (!$emailAddress) {
                        error_log("No email address for user $userId");
                        continue;
                    }
                    $clEmail = null;
                    if (array_key_exists("$listId:$emailName", $this->cache) && isset($this->cache["$listId:$emailName"])) {
                        $clEmail = $this->cache["$listId:$emailName"];
                    } else {
                        $clEmail = new ClipListEmail($listId, $emailName);
                        $this->cache["$listId:$emailName"] = $clEmail;
                    }
                    $config = $clEmail->generateEmailContent($emailAddress);
                    $args = [
                        $listId,
                        $emailName,
                        $userId,
                        $config,
                    ];
                    $this->scheduledJobs->scheduleOnce(time(), self::SEND_QUEUED_EMAILS_ACTION, $args);
                }
            }
        }
    }

    public function sendEmail(int $listId, string $emailName, int $userId, array $emailConfig) {
        // send the email
        $this->emailService->send(
            $emailConfig['emailAddress'],
            $emailConfig['subject'],
            $emailConfig['content'],
            $emailConfig['headers']
        );
        // update user recieved email
        $this->userMeta->addReceivedEmail($userId, $listId, $emailName);
        $nextEmail = $this->meta->getNextEmail($listId, $emailName);
        if ($nextEmail !== null && $nextEmail !== 'last') {
            $this->userMeta->setNextEmailForList($userId, $listId, $nextEmail);
        }
        // This means we have reached the last email for this list.
        if ($nextEmail === 'last') {
            // shoould be renamed as removeSubscribedList is confusing
            // we are setting it to false not really removing it.
            $this->userMeta->removeSubscribedList($userId, $listId);
            $this->userMeta->deleteNextEmailForList($userId, $listId);
        }
    }

    /**
     * Builds the campaign config for the given users.
     *
     * @param array<int, array{
     *     ID: int,
     *     user_email: string,
     *     next_email: string,
     *     next_email_key: string,
     *     display_name: string
     * }> $users Array of user data.
     *
     * @return array<int, array<string,mixed>> Array of campaign config objects.
     */
    private function buildCampaignConfig($users) {
        $lists = [];
        foreach ($users as $user) {
            $user = (array)$user;
            $listId = str_replace($this->userMeta::PREFIX . $this->userMeta->next_email . "_", '', $user['next_email_key']);
            $listId = (int)$listId;
            if (!isset($lists[$listId])) {
                $lists[$listId] = [];
            }
            if(!isset($lists[$listId][$user['next_email']])) {
                $lists[$listId][$user['next_email']] = [];
            }
            $lists[$listId][$user['next_email']][$user['ID']] = $user;
        }
        return $lists;
    }

    /**
     * Get subscribed Paying users.
     *
     * @global wpdb $wpdb
     * @return array Array of user objects (ID, user_login, user_email).
     */
    private function getSubscribedUsers() {
        global $wpdb;
        $umeta = new AimClipListUserMeta();

        $sql = "
        SELECT u.ID, u.user_email, um1.meta_value AS next_email, um1.meta_key as next_email_key, u.display_name
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um1
            ON um1.user_id = u.ID
            AND um1.meta_key LIKE %s
        INNER JOIN {$wpdb->usermeta} um2
            ON um2.user_id = u.ID
            AND um2.meta_key LIKE %s
        WHERE um1.meta_value IS NOT NULL
    ";

        // Prepare the query safely
        $prepared = $wpdb->prepare(
            $sql,
            $wpdb->esc_like($umeta::PREFIX . $umeta->next_email . "_") . "%",
            // we do this as they append the group id to the end of the key
            $wpdb->esc_like('learndash_group_users') . '%'  // LIKE match 
        );

        return $wpdb->get_results($prepared);
    }
}
