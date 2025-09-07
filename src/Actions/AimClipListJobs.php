<?php

namespace Jk\Vts\Actions;

use Jk\Vts\Services\ScheduledJobs;

class AimClipListJobs {
    public ScheduledJobs $scheduledJobs;
    const SEND_EMAILS_ACTION = 'send_aim_clip_list_emails';
    public function __construct(
        public string $path,
        public string $url,
    ) {
        $this->scheduledJobs = new ScheduledJobs();
    }

    public function scheduleActions() {
        $this->scheduledJobs->ensureScheduled(fn() => $this->scheduleSendEmailAction());
    }

    private function scheduleSendEmailAction() {
        $this->scheduledJobs->scheduleRecurring(time(), WEEK_IN_SECONDS, self::SEND_EMAILS_ACTION);
    }
}
