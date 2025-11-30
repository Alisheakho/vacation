<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{id}', function ($user, $id) {
    // التحقق الآمن: هل الآيدي في التوكن يطابق الآيدي المطلوب؟
    return (int) $user->id === (int) $id;
}, ['guards' => ['api']]);