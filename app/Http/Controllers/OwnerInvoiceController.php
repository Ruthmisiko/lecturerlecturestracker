<?php

namespace App\Http\Controllers;

use PDF;
use App\Models\Institution;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** System owner: invoices issued to institutions. */
class OwnerInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->is_system_owner, 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $invoices = Invoice::with('institution')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%' . $request->q . '%')
                ->orWhere('bill_to_name', 'like', '%' . $request->q . '%')))
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', $request->institution_id))
            ->when($request->status === 'overdue', fn ($q) => $q->whereIn('status', ['draft', 'sent'])->whereDate('due_date', '<', today()))
            ->when(in_array($request->status, array_keys(Invoice::STATUSES)), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('issue_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $summary = [
            'outstanding' => Invoice::whereIn('status', ['draft', 'sent'])->sum('total'),
            'overdue'     => Invoice::whereIn('status', ['draft', 'sent'])->whereDate('due_date', '<', today())->sum('total'),
            'paid'        => Invoice::where('status', 'paid')->sum('total'),
            'count'       => Invoice::count(),
        ];

        $institutionOptions = Institution::orderBy('name')->get(['id', 'name']);

        return view('owner.invoices.index', compact('invoices', 'summary', 'institutionOptions'));
    }

    public function create(Request $request)
    {
        $invoice = new Invoice([
            'number'         => Invoice::nextNumber(),
            'issue_date'     => today(),
            'due_date'       => today()->addDays(14),
            'currency'       => config('billing.currency'),
            'status'         => 'draft',
            'institution_id' => $request->integer('institution_id') ?: null,
            'items'          => [['description' => 'Lecturer Tracker subscription', 'quantity' => 1, 'unit_price' => 0]],
        ]);

        return view('owner.invoices.form', ['invoice' => $invoice] + $this->formData());
    }

    public function store(Request $request)
    {
        $invoice = Invoice::create($this->validated($request));

        return redirect()->route('owner.invoices.show', $invoice->id)->with('success', "Invoice {$invoice->number} created.");
    }

    public function show($id)
    {
        return view('owner.invoices.show', ['invoice' => Invoice::with('institution')->findOrFail($id)]);
    }

    public function edit($id)
    {
        return view('owner.invoices.form', ['invoice' => Invoice::findOrFail($id)] + $this->formData());
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($this->validated($request, $invoice));

        return redirect()->route('owner.invoices.show', $invoice->id)->with('success', "Invoice {$invoice->number} updated.");
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return redirect()->route('owner.invoices.index')->with('success', "Invoice {$invoice->number} deleted.");
    }

    /** Quick status change from the list or invoice page. */
    public function status(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $status  = $request->validate(['status' => ['required', Rule::in(array_keys(Invoice::STATUSES))]])['status'];

        $invoice->update([
            'status'  => $status,
            'paid_at' => $status === 'paid' ? ($invoice->paid_at ?? today()) : null,
        ]);

        return back()->with('success', "Invoice {$invoice->number} marked as " . Invoice::STATUSES[$status] . '.');
    }

    public function pdf($id)
    {
        $invoice = Invoice::with('institution')->findOrFail($id);

        return PDF::loadView('owner.invoices.pdf', compact('invoice'))
            ->setPaper('a4')
            ->download($invoice->number . '.pdf');
    }

    // ---------------------------------------------------------------------

    private function formData(): array
    {
        $institutions = Institution::with('owner')->orderBy('name')->get();

        return [
            'institutions' => $institutions,
            // Pre-fill "bill to" from the institution and its account owner.
            'billTo' => $institutions->mapWithKeys(fn ($i) => [$i->id => ['name' => $i->name, 'email' => $i->owner->email ?? '']]),
        ];
    }

    private function validated(Request $request, ?Invoice $invoice = null): array
    {
        $data = $request->validate([
            'number'              => ['required', 'string', 'max:50', Rule::unique('invoices', 'number')->ignore($invoice?->id)],
            'institution_id'      => 'nullable|exists:institutions,id',
            'bill_to_name'        => 'required|string|max:255',
            'bill_to_email'       => 'nullable|email|max:190',
            'issue_date'          => 'required|date',
            'due_date'            => 'nullable|date|after_or_equal:issue_date',
            'currency'            => 'required|string|size:3',
            'status'              => ['required', Rule::in(array_keys(Invoice::STATUSES))],
            'notes'               => 'nullable|string|max:5000',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity'    => 'required|numeric|min:0',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ], [
            'items.required'               => 'Add at least one line item.',
            'items.*.description.required' => 'Every line item needs a description.',
        ]);

        $data['items'] = collect($data['items'])->map(fn ($i) => [
            'description' => trim($i['description']),
            'quantity'    => (float) $i['quantity'],
            'unit_price'  => (float) $i['unit_price'],
        ])->values()->all();

        $data['total']    = round(collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']), 2);
        $data['currency'] = strtoupper($data['currency']);
        $data['paid_at']  = $data['status'] === 'paid' ? ($invoice?->paid_at ?? today()) : null;

        return $data;
    }
}
