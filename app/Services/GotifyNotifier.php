<?php

namespace App\Services;

use App\Models\CifRemovalRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pushes an operator notification to Gotify.
 *
 * Gotify is used instead of e-mail because the shared mail host silently drops
 * outbound mail (it accepts the SMTP transaction and never delivers it).
 *
 * It is enabled only in production: `GOTIFY_ENABLED` is set in
 * `.env.production` and absent everywhere else, so local and test runs never
 * talk to the real server.
 */
final class GotifyNotifier
{
    private const TITLE = 'Cerere nouă de ștergere CIF';

    private const PRIORITY = 8;

    public static function forRemovalRequest(CifRemovalRequest $request): bool
    {
        $lines = [
            'CUI: '.$request->cui,
            'Firma: '.($request->company_name ?? '—'),
            'Solicitant: '.$request->requester_name.' <'.$request->requester_email.'>',
            'Motiv: '.$request->reason,
            'Trimisă: '.($request->created_at?->format('d.m.Y H:i') ?? '—'),
            url('/admin/cif-removal-requests/'.$request->getKey().'/edit'),
        ];

        return self::send(self::TITLE.' '.$request->cui, implode("\n", $lines));
    }

    public static function send(string $title, string $message): bool
    {
        if (! self::enabled()) {
            return false;
        }

        /** @var array<string, mixed> $config */
        $config = config('skip.gotify');

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->post(
                    rtrim((string) $config['url'], '/').'/message?token='.urlencode((string) $config['token']),
                    [
                        'title' => $title,
                        'message' => $message,
                        'priority' => self::PRIORITY,
                    ]
                );

            if ($response->failed()) {
                Log::error('Gotify notification was rejected', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            // A notification must never break the visitor's submission.
            Log::error('Gotify notification could not be sent', ['error' => $exception->getMessage()]);

            return false;
        }
    }

    public static function enabled(): bool
    {
        /** @var array<string, mixed> $config */
        $config = config('skip.gotify');

        return (bool) ($config['enabled'] ?? false)
            && filled($config['url'] ?? null)
            && filled($config['token'] ?? null);
    }
}
