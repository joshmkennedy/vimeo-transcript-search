<?php

namespace Jk\Vts\Forms;

class Flash {
    public static function message(string $id, string $message){
        $key = self::storageKey($id);
        setcookie($key, $message, time() + 60, "/");
    }

    private static function storageKey(string $id){
        return sprintf('vts-flash--%s', $id);
    }

}
