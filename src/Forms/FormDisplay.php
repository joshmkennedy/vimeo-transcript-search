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
       $lists = Collection::make($meta->getSubscribedLists($userId))->filter(fn($listId)=>$listId === true);
       error_log(print_r($lists->toArray(), true));
       return $lists->count() > 0;
    }
}
