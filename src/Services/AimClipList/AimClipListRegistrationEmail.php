<?php

namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Email\GenericEmail;
use Jk\Vts\Services\ScheduledJobs;

class AimClipListRegistrationEmail {
    const SEND_EMAIL_ACTION = 'send_aim_registration_clip_list_email';
    private ClipListMeta $meta;
    private AimClipListUserMeta $userMeta;
    private GenericEmail $email;
    private ScheduledJobs $scheduledJobs;
    public function __construct(
        public string $path,
        public string $url,

    ) {
        $this->meta = new ClipListMeta();
        $this->userMeta = new AimClipListUserMeta();
        $this->email = new GenericEmail();
        $this->scheduledJobs = new ScheduledJobs();
    }

    public function scheduleRegistrationEmail(int $listId, int $userId) {
        $defaultEmailContent = $this->getDefaultEmailContent();
        $this->scheduledJobs->scheduleOnce(time(), self::SEND_EMAIL_ACTION, [
            $listId,
            $userId,
            $defaultEmailContent,
        ]);
    }

    /**
     * Sends the email to the user.
     *
     * TODO: listId may be for a list that has the same form id and so it wont check
     *       if the user has already subscribed to a list that was for the same form.
     *
     * @param int    $listId     The list id.
     * @param int    $userId     The user id.
     * @param string $content    The email content.
     **/
    public function sendEmail(int $listId, int $userId, string $content) {
        $user = get_user_by('id', $userId);
        $usersLists = $this->userMeta->getSubscribedLists($userId);
        if (! array_key_exists($listId, $usersLists)) {
            return;
        }
        $emailAddress = $user->user_email;
        $this->email->sendEmail($emailAddress, "You have successfully registered for an AiM Starting Point.", $content);
        $nextEmail = "week_1_videos_for_this_week";
        $this->userMeta->setNextEmailForList($userId, $listId, $nextEmail);
    }

    public function getDefaultEmailContent() {
        $introCourseURL = site_url() . '/aim/essentials';
        $content = "You have successfully registered for an AiM Starting Point.<br/><br/>";
        $content .= "You will receive emails with videos and resources, currated for you based on your assessment to help you get started with Ai Marketing Academy.<br/>";
        $content .= "In the meantime, if you have not already, make sure you check out the <a href='$introCourseURL'>AiM Essentials</a> course. It will give you everything you need to know to get the most out of Ai Marketing Academy.<br/>";

        $content .= "";
        return $content;
    }
}
