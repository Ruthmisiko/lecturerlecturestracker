<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasAccess('email-template.manage'), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $templates = EmailTemplate::orderByDesc('is_default')->orderBy('name')->get();

        return view('email_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('email_templates.form', ['template' => new EmailTemplate([
            'subject' => 'Payslip – {name} – {period}',
            'body'    => "Dear {name},\n\nPlease find attached your payslip for {period}.\n\nHours: {hours}\nGross amount: {gross}\nNet pay: {net}\n\nKind regards,\n{institution}",
        ])]);
    }

    public function store(Request $request)
    {
        $template = EmailTemplate::create($this->validated($request) + ['user_id' => Auth::user()->ownerId()]);

        if ($request->boolean('is_default')) {
            $template->makeDefault();
        }

        Flash::success('Email template saved.');

        return redirect()->route('email-templates.index');
    }

    public function edit($id)
    {
        return view('email_templates.form', ['template' => EmailTemplate::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $template->update($this->validated($request));

        $request->boolean('is_default') ? $template->makeDefault() : $template->update(['is_default' => false]);

        Flash::success('Email template updated.');

        return redirect()->route('email-templates.index');
    }

    public function destroy($id)
    {
        EmailTemplate::findOrFail($id)->delete();

        Flash::success('Email template deleted.');

        return redirect()->route('email-templates.index');
    }

    public function makeDefault($id)
    {
        EmailTemplate::findOrFail($id)->makeDefault();

        Flash::success('Default template updated.');

        return redirect()->route('email-templates.index');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'    => 'required|string|max:100',
            'subject' => 'required|string|max:200',
            'body'    => 'required|string|max:10000',
        ]);
    }
}
