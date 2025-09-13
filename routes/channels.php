<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// SSL Monitoring Channels
Broadcast::channel('ssl-monitoring', function ($user) {
    return auth()->check(); // All authenticated users can access SSL monitoring
});

Broadcast::channel('ssl-monitoring.website.{websiteId}', function ($user, $websiteId) {
    // Users can only access channels for websites they own or have access to via team
    return \App\Models\Website::where('id', $websiteId)
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhereHas('user.teams', function ($teamQuery) use ($user) {
                      $teamQuery->whereHas('members', function ($memberQuery) use ($user) {
                          $memberQuery->where('user_id', $user->id);
                      });
                  });
        })
        ->exists();
});

// Uptime Monitoring Channels
Broadcast::channel('uptime-monitoring', function ($user) {
    return auth()->check(); // All authenticated users can access uptime monitoring
});

Broadcast::channel('uptime-monitoring.website.{websiteId}', function ($user, $websiteId) {
    // Users can only access channels for websites they own or have access to via team
    return \App\Models\Website::where('id', $websiteId)
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhereHas('user.teams', function ($teamQuery) use ($user) {
                      $teamQuery->whereHas('members', function ($memberQuery) use ($user) {
                          $memberQuery->where('user_id', $user->id);
                      });
                  });
        })
        ->exists();
});
