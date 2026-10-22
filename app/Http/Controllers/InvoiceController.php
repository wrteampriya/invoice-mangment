<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected InvoiceService $invoices) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()
            ->where('user_id', $request->user()->id);

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            $this->applyRequestSort($query, $request);
            $this->applyRequestSearch($query, $request);

            $limit = (int) $request->input('limit', 10);
            if ($limit < 1 || $limit > 500) {
                $limit = 10;
            }
            $offset = max(0, (int) $request->input('offset', 0));

            $total = (clone $query)->count();
            $rows = $query
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(fn (Invoice $invoice) => $this->transformInvoice($invoice))
                ->values()
                ->all();

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        }

        $this->applyRequestSearch($query, $request);
        $this->applyRequestSort($query, $request);
        $query->with('customer');

        $invoices = (clone $query)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $base = Invoice::query()->where('user_id', $request->user()->id);

        $total = (clone $base)->count();
        $paid = (clone $base)->paid()->count();
        $overdue = (clone $base)->overdue()->count();

        $stats = [
            'total' => $total,
            'overdue' => $overdue,
            'sent' => $total - $paid - $overdue,
            'paid' => $paid,
            'currencies' => (clone $base)->distinct()->count('currency'),
        ];

        return view('invoices.index', compact('invoices', 'stats'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Invoice::class);

        $invoice = new Invoice([
            'invoice_date' => now()->toDateString(),
            'currency' => Invoice::CURRENCIES[0],
            'tax_type' => Invoice::TAX_TYPE_NONE,
        ]);

        return view('invoices.create', [
            'invoice' => $invoice,
            'invoiceNumber' => $this->invoices->nextInvoiceNumber($request->user()->id),
            'customers' => $this->customerOptions($request->user()->id),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $this->invoices->createInvoice((int) $request->user()->id, $request->validated(), $request->itemRows());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice created.');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'customer']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    public function edit(Request $request, Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $invoice->load('items');

        return view('invoices.edit', [
            'invoice' => $invoice,
            'invoiceNumber' => $invoice->invoice_number,
            'customers' => $this->customerOptions($request->user()->id),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice = $this->invoices->updateInvoice($invoice, $request->validated(), $request->itemRows());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice updated.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('status', 'Invoice deleted.');
    }

    /**
     * Download this single invoice as a PDF (owner only).
     */
    public function pdf(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'payments', 'customer', 'user']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->stream($invoice->invoice_number.'.pdf');
    }

    /**
     * Customer dropdown options for the form (only the user's own customers).
     */
    protected function customerOptions(int $userId): Collection
    {
        return Customer::query()
            ->where('user_id', $userId)
            ->orderBy('company_name')
            ->orderBy('name')
            ->get(['id', 'customer_type', 'name', 'company_name', 'country_code']);
    }

    /**
     * Whitelisted sortable columns for the table JSON feed.
     */
    protected function applyRequestSort(Builder $query, Request $request): void
    {
        $sortableColumns = [
            'id' => 'id',
            'invoice_number' => 'invoice_number',
            'invoice_date' => 'invoice_date',
            'due_date' => 'due_date',
            'grand_total' => 'grand_total',
            'created_at' => 'created_at',
        ];

        $sort = $sortableColumns[$request->input('sort', 'id')] ?? 'id';
        $order = strtolower((string) ($request->input('order', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $order)->orderBy('id', 'desc');
    }

    /**
     * Free text filter over the invoice number and the customer columns.
     */
    protected function applyRequestSearch(Builder $query, Request $request): void
    {
        $term = trim((string) $request->input('search', $request->input('q', '')));

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $query) use ($like) {
            $query->where('invoice_number', 'like', $like)
                ->orWhereHas('customer', fn ($customer) => $customer->where(function ($customer) use ($like) {
                    $customer->where('name', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                }));
        });
    }

    /**
     * Raw display fields (nothing pre-rendered) consumed by the table formatters.
     * Status is bucketed into three display states: paid / overdue / sent.
     */
    protected function transformInvoice(Invoice $invoice): array
    {
        // Status buckets into three display states: paid / overdue / sent.
        // The form-selected stored status always wins; otherwise derived.
        if (in_array($invoice->status, ['paid', 'overdue'], true)) {
            $status = $invoice->status;
        } elseif ($invoice->status === 'sent' && $invoice->balance <= 0) {
            $status = 'paid';
        } elseif (
            $invoice->status === 'sent'
            && $invoice->balance > 0
            && $invoice->due_date
            && $invoice->due_date->isPast()
        ) {
            $status = 'overdue';
        } else {
            $status = 'sent';
        }

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer?->display_name,
            'invoice_date' => optional($invoice->invoice_date)->format('d M Y'),
            'due_date' => optional($invoice->due_date)->format('d M Y'),
            'due_date_raw' => optional($invoice->due_date)->format('Y-m-d'),
            'currency' => $invoice->currency,
            'grand_total' => (float) $invoice->grand_total,
            'grand_total_display' => $invoice->formatMoney((float) $invoice->grand_total),
            'items_count' => $invoice->items()->count(),
            'status' => $status,
            'show_url' => route('invoices.show', $invoice),
            'pdf_url' => route('invoices.pdf', $invoice),
            'edit_url' => route('invoices.edit', $invoice),
            'delete_url' => route('invoices.destroy', $invoice),
        ];
    }
}
