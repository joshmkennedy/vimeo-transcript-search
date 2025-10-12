<?php

namespace Jk\Vts\Forms;

use Illuminate\Support\Collection;
use Jk\Vts\Services\AimClipList\AimClipListUserMeta;

class FormDisplay {
    public function __construct() {
        global $aimFormDisplay;
        $aimFormDisplay = self::class;
    }

    public static function userHasActiveList(int $userId){
       $meta = new AimClipListUserMeta();
       $lists = Collection::make($meta->getSubscribedLists($userId))->filter(function($value, $key) {
    if (is_bool($value)) {
        return $value === true;
    }
    return is_array($value) && isset($value['subscribed_on']) && $value['finished_on'] === null;
})->keys();
       return $lists->count() > 0;
    }
}
