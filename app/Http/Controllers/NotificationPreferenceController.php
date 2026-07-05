<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_email_new_submission' => ['nullable', 'boolean'],
            'notify_email_submission_reviewed' => ['nullable', 'boolean'],
            'notify_email_workflow' => ['nullable', 'boolean'],
            'notify_sms_workflow' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $user->notify_email_new_submission = (bool) ($validated['notify_email_new_submission'] ?? false);
        $user->notify_email_submission_reviewed = (bool) ($validated['notify_email_submission_reviewed'] ?? false);
        $user->notify_email_workflow = (bool) ($validated['notify_email_workflow'] ?? false);
        $user->notify_sms_workflow = (bool) ($validated['notify_sms_workflow'] ?? false);
        $user->save();

        return redirect()->route('notifications.index')->with('status', 'Notification preferences updated.');
    }
}
