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

    public function scheduleRegistrationEmail(int $listId, $userId) {
        $defaultEmailContent = $this->getDefaultEmailContent();
        $this->scheduledJobs->scheduleOnce(time(), self::SEND_EMAIL_ACTION, [$listId, $userId, $defaultEmailContent]);
    }

    public function sendEmail(int $listId, int $userId, string $content) {
        $user = get_user_by('id', $userId);
        $usersLists = $this->userMeta->getSubscribedLists($userId);
        if (! array_key_exists($listId, $usersLists)) {
            return;
        }
        $emailAddress = $user->user_email;
        $this->email->sendEmail($emailAddress, "You have successfully registered for an AiM Learning Path.", $content);
        $nextEmail = "week_1_videos_for_this_week";
        $this->userMeta->setNextEmailForList($userId, $listId, $nextEmail);
    }

    public function getDefaultEmailContent() {
        $content = "You have successfully registered for an AiM Learning Path.<br/><br/>";
        $content .= "You will receive emails with videos and resources, currated for you based on your assessment to help you get started with Ai Marketing Academy.<br/><br/>";

        $content .= "";
        return $content;
    }
}
