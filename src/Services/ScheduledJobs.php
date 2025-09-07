<?php

namespace Jk\Vts\Services;


class ScheduledJobs {

    public function ensureScheduled(callable $callback) {
        if (function_exists('as_supports') && as_supports('ensure_recurring_actions_hook')) {
            // Preferred: runs periodically in the background.
            add_action('action_scheduler_ensure_recurring_actions', $callback);
        } elseif (is_admin()) {
            // Fallback: runs on every admin request.
            $callback();
        }
    }

    public function scheduleRecurring(int $timestamp, int $interval, string $hook, array $args = []) {
        if (! as_has_scheduled_action($hook)) {
            as_schedule_recurring_action(
                $timestamp,
                $interval,
                $hook,
                $args
            );
        }
    }

    public function scheduleOnce(int $timestamp, string $hook, array $args = []) {
        if (! as_has_scheduled_action($hook)) {
            as_schedule_single_action(
                $timestamp,
                $hook,
                $args
            );
        }
    }
}
