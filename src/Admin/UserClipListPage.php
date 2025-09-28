<?php

namespace Jk\Vts\Admin;

use Jk\Vts\Services\AimClipList\AimClipListUserMeta;

class UserClipListPage {

    public function __construct() {
        // No side effects; hooks added in plugin bootstrap
    }

    public function render_table($user) {
        if (!current_user_can('edit_users')) {
            return;
        }

        $userId = $user->ID;
        $userMeta = new AimClipListUserMeta();
        $subscribed = $userMeta->getSubscribedLists($userId);

        if (empty($subscribed)) {
            echo '<h3>ClipList Subscriptions</h3><p>No ClipLists subscribed.</p>';
            return;
        }

        $listIds = array_keys($subscribed);
        $posts = get_posts([
            'post_type' => 'aim-clip-list',
            'post__in' => $listIds,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'private'],
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        if (empty($posts)) {
            echo '<h3>ClipList Subscriptions</h3><p>No valid ClipLists found for subscriptions.</p>';
            return;
        }

        echo '<div class="user-clip-list-section"><h3>ClipList Subscriptions</h3>';
        echo '<table class="widefat fixed striped"><thead><tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Date Subscribed</th>
            <th>Last Email Sent</th>
        </tr></thead><tbody>';

        foreach ($posts as $post) {
            $listId = $post->ID;
            $status = $userMeta->getSubscriptionStatus($userId, $listId);
            $rowClass = ($status === 'inactive') ? ' class="inactive"' : '';
            $statusBadge = '<span class="status status-' . esc_attr($status) . '">' . ucfirst($status) . '</span>';

            $subDate = $userMeta->getSubscriptionDate($userId, $listId);
            $subDateDisplay = $subDate ? date('Y-m-d', $subDate) : 'N/A';

            $lastEmail = $userMeta->getLastEmailSentForList($userId, $listId);
            $lastEmailDisplay = $lastEmail ? $lastEmail[0] . ' on ' . date('Y-m-d', strtotime($lastEmail[1])) : 'None';

            echo '<tr' . $rowClass . '>
                <td>' . esc_html($listId) . '</td>
                <td>' . esc_html($post->post_title) . '</td>
                <td>' . $statusBadge . '</td>
                <td>' . esc_html($subDateDisplay) . '</td>
                <td>' . esc_html($lastEmailDisplay) . '</td>
            </tr>';
        }

        echo '</tbody></table></div>';
    }
}
