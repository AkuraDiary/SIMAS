<?php

namespace App\Filament\Livewire;

use App\Models\User;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Filament\Notifications\Notification;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    /**
     * Map of unread notification IDs that were already loaded or displayed.
     * Stored as key-value pairs [notification_id => true] for O(1) lookups.
     *
     * @var array<string, bool>
     */
    public array $knownNotificationIds = [];

    public bool $isInitialized = false;

    public function mount(): void
    {
        // Populate existing unread notification IDs on initial mount
        // so that existing unread notifications do NOT trigger pop-up spam upon loading/refreshing a page.
        $unreadIds = $this->getUnreadNotificationsQuery()
            ->limit(50)
            ->pluck('id')
            ->toArray();

        $this->knownNotificationIds = array_fill_keys($unreadIds, true);
        $this->isInitialized = true;
    }

    public function rendering(): void
    {
        if (! $this->isInitialized) {
            return;
        }

        $user = $this->getUser();
        if (! $user) {
            return;
        }

        // Query any new unread notifications that arrived since the last check
        $newNotifications = $this->getUnreadNotificationsQuery()
            ->whereNotIn('id', array_keys($this->knownNotificationIds))
            ->latest()
            ->get();

        if ($newNotifications->isEmpty()) {
            return;
        }

        $wantsPopup = ($user instanceof User)
            ? $user->wantsNotification('notifikasi_popup', 'popup')
            : true;

        foreach ($newNotifications as $dbNotification) {
            $this->knownNotificationIds[$dbNotification->id] = true;

            if ($wantsPopup) {
                $data = $dbNotification->data ?? [];

                $toast = Notification::make()
                    ->title($data['title'] ?? 'Surat Masuk Baru')
                    ->body($data['body'] ?? null)
                    ->icon($data['icon'] ?? null)
                    ->iconColor($data['iconColor'] ?? null)
                    // ->color($data['color'] ?? 'primary')
                    ->status($data['status'] ?? null);

                $toast->send();
            }
        }

        // Limit memory footprint of knownNotificationIds for long sessions
        if (count($this->knownNotificationIds) > 100) {
            $this->knownNotificationIds = array_slice($this->knownNotificationIds, -50, null, true);
        }
    }
}
