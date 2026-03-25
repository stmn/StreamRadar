<?php

namespace App\Mail;

use App\Models\AlertRule;
use App\Models\Stream;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StreamAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AlertRule $rule,
        public Stream $stream,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[StreamRadar] {$this->stream->user_name} is live — {$this->rule->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $thumbnail = $this->stream->getThumbnailUrlSized(440, 248);
        $url = $this->stream->getTwitchUrl();
        $viewers = number_format($this->stream->viewer_count);
        $category = $this->stream->category?->name ?? 'Unknown';

        return <<<HTML
        <div style="font-family: sans-serif; max-width: 500px; margin: 0 auto;">
            <h2 style="color: #9147ff;">StreamRadar Alert</h2>
            <p><strong>{$this->stream->user_name}</strong> is live!</p>
            <p><em>{$this->stream->title}</em></p>
            <p>{$viewers} viewers &middot; {$category} &middot; {$this->stream->language}</p>
            <p><a href="{$url}" style="display: inline-block; padding: 10px 20px; background: #9147ff; color: white; text-decoration: none; border-radius: 8px;">Watch on Twitch</a></p>
            <p style="color: #888; font-size: 12px;">Alert rule: {$this->rule->name}</p>
        </div>
        HTML;
    }
}
