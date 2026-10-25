<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\EmailConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmailConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasAccess('email-settings.manage'), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $configs = EmailConfiguration::orderByDesc('is_default')->orderBy('name')->get();

        return view('email_configurations.index', compact('configs'));
    }

    public function create()
    {
        return view('email_configurations.form', ['account' => new EmailConfiguration(['port' => 587, 'encryption' => 'tls'])]);
    }

    public function store(Request $request)
    {
        $config = EmailConfiguration::create($this->validated($request) + ['user_id' => Auth::user()->ownerId()]);

        if ($request->boolean('is_default') || EmailConfiguration::count() === 1) {
            $config->makeDefault();
        }

        Flash::success('Email account saved. Use "Send test" to check it works.');

        return redirect()->route('email-configurations.index');
    }

    public function edit($id)
    {
        return view('email_configurations.form', ['account' => EmailConfiguration::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $config = EmailConfiguration::findOrFail($id);
        $data   = $this->validated($request, $config);

        // Blank password keeps the saved one.
        if (!$request->filled('password')) {
            unset($data['password']);
        }

        $config->update($data);

        if ($request->boolean('is_default')) {
            $config->makeDefault();
        }

        Flash::success('Email account updated.');

        return redirect()->route('email-configurations.index');
    }

    public function destroy($id)
    {
        $config = EmailConfiguration::findOrFail($id);
        $wasDefault = $config->is_default;
        $config->delete();

        if ($wasDefault && ($next = EmailConfiguration::orderBy('id')->first())) {
            $next->makeDefault();
        }

        Flash::success('Email account deleted.');

        return redirect()->route('email-configurations.index');
    }

    public function makeDefault($id)
    {
        EmailConfiguration::findOrFail($id)->makeDefault();

        Flash::success('Default sending account updated.');

        return redirect()->route('email-configurations.index');
    }

    /** Sends a short test email to the logged-in user through this account. */
    public function test(Request $request, $id)
    {
        $config = EmailConfiguration::findOrFail($id);
        $to     = $request->input('to') ?: Auth::user()->email;

        try {
            $config->mailer()->raw(
                "This is a test email from Lecturer Tracker, sent through the \"{$config->name}\" account. It works!",
                fn ($m) => $m->to($to)->from($config->from_address, $config->from_name)->subject('Lecturer Tracker: test email')
            );
            Flash::success("Test email sent to {$to}. Check the inbox (and spam folder).");
        } catch (\Throwable $e) {
            report($e);
            Flash::error('Sending failed: ' . $e->getMessage());
        }

        return redirect()->route('email-configurations.index');
    }

    private function validated(Request $request, ?EmailConfiguration $config = null): array
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'from_name'    => 'required|string|max:150',
            'from_address' => 'required|email|max:190',
            'host'         => 'required|string|max:190',
            'port'         => 'required|integer|min:1|max:65535',
            'encryption'   => ['nullable', Rule::in(['tls', 'ssl'])],
            'username'     => 'nullable|string|max:190',
            'password'     => 'nullable|string|max:500',
        ]);

        $data['encryption'] = $data['encryption'] ?? null;

        return $data;
    }
}
