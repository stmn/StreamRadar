<?php

namespace App\Services;

use App\Mail\StreamAlertMail;
use App\Models\AlertRule;
use App\Models\Setting;
use App\Models\Stream;
use App\Models\StreamAlertTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * @return array<array{rule: AlertRule, stream: Stream}>
     */
    public function checkAlerts(Stream $stream, bool $isNew): array
    {
        $triggeredAlerts = [];
        $rules = AlertRule::where('is_active', true)->get();

        foreach ($rules as $rule) {
            if (! $rule->matchesStream($stream)) {
                continue;
            }

            if ($rule->match_mode === 'first_time') {
                $alreadyTriggered = StreamAlertTracking::where('alert_rule_id', $rule->id)
                    ->where('stream_twitch_id', $stream->twitch_id)
                    ->exists();

                if ($alreadyTriggered) {
                    continue;
                }
            }

            StreamAlertTracking::create([
                'alert_rule_id' => $rule->id,
                'stream_twitch_id' => $stream->twitch_id,
                'streamer_login' => $stream->user_login,
                'triggered_at' => now(),
            ]);

            $triggeredAlerts[] = [
                'rule' => $rule,
                'stream' => $stream,
            ];
        }

        if (! empty($triggeredAlerts)) {
            $this->sendEmailAlerts($triggeredAlerts);
            $this->sendDiscordAlerts($triggeredAlerts);
            $this->sendWebhookAlerts($triggeredAlerts);
        }

        return $triggeredAlerts;
    }

    private function sendDiscordAlerts(array $alerts): void
    {
        $webhookUrl = Setting::get('discord_webhook_url');
        if (! $webhookUrl) {
            return;
        }

        foreach ($alerts as $alert) {
            if (! $alert['rule']->notify_discord) {
                continue;
            }

            $stream = $alert['stream'];
            $rule = $alert['rule'];
            $twitchUrl = $stream->getTwitchUrl();
            $thumbnail = $stream->getThumbnailUrlSized(440, 248);
            $viewers = number_format($stream->viewer_count);
            $category = $stream->category?->name ?? 'Unknown';

            try {
                Http::post($webhookUrl, [
                    'embeds' => [[
                        'title' => "{$stream->user_name} is live!",
                        'url' => $twitchUrl,
                        'description' => $stream->title,
                        'color' => 0x9147ff,
                        'fields' => [
                            ['name' => 'Category', 'value' => $category, 'inline' => true],
                            ['name' => 'Viewers', 'value' => $viewers, 'inline' => true],
                            ['name' => 'Language', 'value' => strtoupper($stream->language ?? '?'), 'inline' => true],
                        ],
                        'thumbnail' => $stream->profile_image_url ? ['url' => $stream->profile_image_url] : null,
                        'image' => $thumbnail ? ['url' => $thumbnail] : null,
                        'footer' => ['text' => "Alert: {$rule->name}"],
                        'timestamp' => now()->toIso8601String(),
                    ]],
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to send Discord alert: {$e->getMessage()}");
            }
        }
    }

    private function sendWebhookAlerts(array $alerts): void
    {
        $webhookUrl = Setting::get('webhook_url');
        if (! $webhookUrl) {
            return;
        }

        foreach ($alerts as $alert) {
            $stream = $alert['stream'];
            $rule = $alert['rule'];

            try {
                Http::post($webhookUrl, [
                    'event' => 'stream.alert',
                    'rule' => $rule->name,
                    'channel' => $stream->user_name,
                    'channel_login' => $stream->user_login,
                    'title' => $stream->title,
                    'game' => $stream->game_name ?? $stream->category?->name,
                    'viewers' => $stream->viewer_count,
                    'language' => $stream->language,
                    'url' => $stream->getTwitchUrl(),
                    'thumbnail' => $stream->getThumbnailUrlSized(440, 248),
                    'avatar' => $stream->profile_image_url,
                    'started_at' => $stream->started_at?->toIso8601String(),
                    'is_mature' => $stream->is_mature,
                    'tags' => $stream->tags,
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to send webhook alert: {$e->getMessage()}");
            }
        }
    }

    private function sendEmailAlerts(array $alerts): void
    {
        $recipient = Setting::get('mail_to');
        if (! $recipient) {
            return;
        }

        foreach ($alerts as $alert) {
            if (! $alert['rule']->notify_email) {
                continue;
            }

            try {
                Mail::to($recipient)->send(new StreamAlertMail($alert['rule'], $alert['stream']));
            } catch (\Exception $e) {
                Log::warning("Failed to send alert email: {$e->getMessage()}");
            }
        }
    }
}
