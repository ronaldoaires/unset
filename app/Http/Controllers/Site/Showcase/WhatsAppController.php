<?php

namespace App\Http\Controllers\Site\Showcase;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Falar no WhatsApp" buttons post here, and only this redirect knows the
 * number. Crawlers that harvest numbers read the HTML and follow links, but
 * do not submit forms with a CSRF token, so the number is never exposed to
 * them. The message comes pre-filled with the service the visitor was on.
 */
class WhatsAppController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $number = preg_replace('/\D/', '', (string) config('showcase.contact.whatsapp'));

        // Not configured yet: the quote form is the next best thing.
        if ($number === '') {
            return redirect()->route('quotes.create', array_filter($request->only(['servico', 'projeto'])));
        }

        return redirect()->away('https://wa.me/' . $number . '?text=' . rawurlencode($this->message($request)))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function message(Request $request): string
    {
        $project = $request->filled('projeto')
            ? Project::query()->where('is_active', true)->where('slug', $request->input('projeto'))->first(['title'])
            : null;

        $service = $request->filled('servico')
            ? Service::query()->where('is_active', true)->where('slug', $request->input('servico'))->first(['name'])
            : null;

        return match (true) {
            $project !== null => 'Olá! Vi o projeto "' . $project->title . '" no site e quero um orçamento parecido.',
            $service !== null => 'Olá! Vim pelo site e quero um orçamento de ' . mb_strtolower($service->name) . '.',
            default           => 'Olá! Vim pelo site e quero mais informações.',
        };
    }
}
