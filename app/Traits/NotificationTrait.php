<?php

namespace App\Traits;

use App\Models\Notification;

trait NotificationTrait
{
    public function createNotification($user = null, $admin = null, $message = '', $type = 'info', $relatedModel = null, $relatedModelId = null)
    {
        return Notification::create([
            'user_id' => $user ? $user->id : null,
            'admin_id' => $admin ? $admin->id : null,
            'message' => $message,
            'type' => $type,
            'related_model' => $relatedModel,
            'related_model_id' => $relatedModelId,
        ]);
    }
}
