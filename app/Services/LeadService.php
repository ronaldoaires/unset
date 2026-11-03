<?php

namespace App\Services;

use App\Enums\LeadType;
use App\Http\Middleware\CaptureUtm;
use App\Models\Lead;
use App\Notifications\LeadReceivedNotification;
use App\Notifications\NewLeadNotification;
use App\Rules\NotDisposableEmail;
use App\Rules\NoSpamContent;
use App\Rules\PersonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Saves quote requests and "Fale conosco" messages and sends the e-mails.
 * The lead is stored before any e-mail goes out, so a mail failure never
 * loses a client.
 */
class LeadService
{
    /** Same e-mail + same message within this window is a double submission. */
    private const DUPLICATE_MINUTES = 10;

    /**
     * Validation rules shared by both forms for the contact fields.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function contactRules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:2', 'max:100', new PersonName()],
            'email' => [
                'required',
                'string',
                'max:255',
                // A real mail server for the domain: the auto-reply must not bounce.
                Rule::email()->rfcCompliant(strict: true)->validateMxRecord()->preventSpoofing(),
                new NotDisposableEmail(),
            ],
            'message' => ['required', 'string', 'min:10', 'max:3000', new NoSpamContent()],
        ];
    }

    /**
     * Normalizes the raw input the same way for every form.
     */
    public static function normalize(Request $request, array $map = []): void
    {
        $field = fn (string $name) => $map[$name] ?? $name;

        $request->merge([
            $field('name')  => PersonName::normalize($request->input($field('name'))),
            $field('email') => Str::lower(trim((string) $request->input($field('email')))),
        ]);

        if ($request->has($field('phone'))) {
            $request->merge([$field('phone') => preg_replace('/\D/', '', (string) $request->input($field('phone')))]);
        }
    }

    /**
     * @param  array<string, mixed>  $data  validated form data
     */
    public function capture(LeadType $type, array $data, Request $request): Lead
    {
        $duplicate = Lead::query()
            ->where('email', $data['email'])
            ->where('message', $data['message'])
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_MINUTES))
            ->first();

        if ($duplicate) {
            return $duplicate;
        }

        $utm = (array) $request->session()->get(CaptureUtm::SESSION_KEY, []);

        $lead = Lead::create([
            'type'                => $type,
            'name'                => $data['name'],
            'email'               => $data['email'],
            'phone'               => $data['phone'] ?? null,
            'city'                => $data['city'] ?? null,
            'state'               => $data['state'] ?? null,
            'service_id'          => $data['service_id'] ?? null,
            'project_id'          => $data['project_id'] ?? null,
            'user_id'             => $request->user()?->id,
            'message'             => $data['message'],
            'details'             => $data['details'] ?? null,
            'source_url'          => $this->sourceUrl($request),
            'utm_source'          => $utm['utm_source'] ?? null,
            'utm_medium'          => $utm['utm_medium'] ?? null,
            'utm_campaign'        => $utm['utm_campaign'] ?? null,
            'ip'                  => $request->ip(),
            'user_agent'          => mb_substr((string) $request->userAgent(), 0, 500),
            'privacy_accepted_at' => now(),
        ]);

        $this->notify($lead);

        return $lead;
    }

    /**
     * Page the form was sent from, only when it is one of ours.
     */
    private function sourceUrl(Request $request): ?string
    {
        $url = (string) $request->headers->get('referer');

        return $url !== '' && str_starts_with($url, url('/'))
            ? mb_substr($url, 0, 500)
            : null;
    }

    /**
     * No queue worker on the shared hosting, so mail is sent right away.
     * A failure is reported to the log but never shown to the visitor.
     */
    private function notify(Lead $lead): void
    {
        $lead->loadMissing('service');

        $inbox = config('showcase.contact.leads_email');

        try {
            if (filled($inbox)) {
                Notification::route('mail', $inbox)->notify(new NewLeadNotification($lead));
            }

            Notification::route('mail', $lead->email)->notify(new LeadReceivedNotification($lead));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
