<?php

namespace App\Http\Controllers;

use App\Mail\PayslipMail;
use App\Models\Department;
use App\Models\EmailConfiguration;
use App\Models\EmailTemplate;
use App\Models\Institution;
use App\Models\Lecturer;
use App\Models\PayslipEmail;
use App\Services\LecturerPayroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Email many lecturers at once: a message (placeholders filled per lecturer),
 * optionally with each one's payslip PDF for the chosen period and payable statuses.
 */
class BulkEmailController extends DashboardController
{
    public const MAX_RECIPIENTS = 300;

    public function create(Request $request)
    {
        $this->authorizeAccess('bulk-email.send');

        [$from, $to] = $this->period($request);
        $statuses    = $this->statuses($request);

        return view('bulk_email.create', [
            'rows'        => (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'configs'     => EmailConfiguration::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'from_address', 'is_default']),
            'templates'   => EmailTemplate::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'subject', 'body', 'is_default']),
            'from'        => $from,
            'to'          => $to,
            'statuses'    => $statuses,
            'periodText'  => $this->periodText($from, $to),
        ]);
    }

    public function send(Request $request)
    {
        $this->authorizeAccess('bulk-email.send');

        $data = $request->validate([
            'email_configuration_id' => 'required|integer',
            'lecturer_ids'           => 'required|array|min:1|max:' . self::MAX_RECIPIENTS,
            'lecturer_ids.*'         => 'integer',
            'subject'                => 'required|string|max:200',
            'body'                   => 'required|string|max:10000',
            'attach_payslip'         => 'nullable|boolean',
        ], [
            'email_configuration_id.required' => 'Add an email account under Email Settings first.',
            'lecturer_ids.required'           => 'Tick at least one lecturer.',
            'lecturer_ids.max'                => 'Send to at most ' . self::MAX_RECIPIENTS . ' lecturers at a time.',
        ]);

        $config = EmailConfiguration::findOrFail($data['email_configuration_id']);
        [$from, $to] = $this->period($request);
        $statuses    = $this->statuses($request);
        $attach      = $request->boolean('attach_payslip');
        $period      = $this->periodText($from, $to);
        $institution = Institution::find(Auth::user()->currentInstitutionId())?->name ?: 'Accounts Office';

        // Payroll rows are only built for lecturers in the user's scope, so other IDs are ignored.
        $rows      = (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows()->keyBy('id');
        $lecturers = Lecturer::with('department', 'institution')->whereIn('id', $data['lecturer_ids'])->get()
            ->filter(fn ($l) => $rows->has($l->id))->sortBy('name');

        set_time_limit(0);
        $mailer  = $config->mailer();
        $results = [];

        foreach ($lecturers as $lecturer) {
            $row    = $rows->get($lecturer->id);
            $result = ['name' => $lecturer->name, 'to' => $lecturer->email];

            if (!$lecturer->email) {
                $results[] = $result + ['status' => 'skipped', 'note' => 'No email address saved'];
                continue;
            }
            if ($attach && $row->hours <= 0) {
                $results[] = $result + ['status' => 'skipped', 'note' => 'No payable hours in this period, so no payslip'];
                continue;
            }

            $subject = EmailTemplate::fillPlaceholders($data['subject'], $row, $period, $institution);
            $body    = EmailTemplate::fillPlaceholders($data['body'], $row, $period, $institution);

            try {
                $mailer->to($lecturer->email)->send(new PayslipMail(
                    $subject, $body,
                    $attach ? $this->payslipPdfFor($lecturer, $row, $from, $to, $statuses)->output() : null,
                    $attach ? $this->payslipFileName($lecturer) : null,
                    $config->from_address, $config->from_name,
                ));
            } catch (\Throwable $e) {
                report($e);
                $results[] = $result + ['status' => 'failed', 'note' => $e->getMessage()];
                continue;
            }

            if ($attach) {
                PayslipEmail::create([
                    'lecturer_id'            => $lecturer->id,
                    'email_configuration_id' => $config->id,
                    'to'                     => $lecturer->email,
                    'subject'                => $subject,
                    'period_from'            => $from,
                    'period_to'              => $to,
                    'user_id'                => Auth::id(),
                ]);
            }

            $results[] = $result + ['status' => 'sent', 'note' => $attach ? 'With payslip' : ''];
        }

        // Redirect so a page refresh can't send everything again.
        return redirect()->route('bulk-email.sent')->with('bulkEmail', [
            'results' => $results,
            'subject' => $data['subject'],
            'backUrl' => route('bulk-email.create', array_filter(['from' => $from, 'to' => $to, 'statuses' => $statuses])),
        ]);
    }

    public function sent()
    {
        $this->authorizeAccess('bulk-email.send');

        $sent = session('bulkEmail');
        if (!$sent) {
            return redirect()->route('bulk-email.create');
        }

        return view('bulk_email.result', ['results' => collect($sent['results'])] + $sent);
    }

    /** e.g. "all lectures to date" or "01 Oct 2026 to 31 Oct 2026" (as in the Email Payslip window). */
    private function periodText(?string $from, ?string $to): string
    {
        if (!$from && !$to) {
            return 'all lectures to date';
        }

        return ($from ? Carbon::parse($from)->format('d M Y') : 'the start') . ' to ' . ($to ? Carbon::parse($to)->format('d M Y') : 'today');
    }
}
