<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            $query = Customer::ownedBy($request->user()->id);

            $search = $request->input('search', $request->input('q'));
            if ($search) {
                $query->search($search);
            }

            if (in_array($request->input('type'), Customer::CUSTOMER_TYPES, true)) {
                $query->where('customer_type', $request->input('type'));
            }

            if ($request->has('offset') || $request->input('sidePagination') === 'server') {
                $total = (clone $query)->count();

                $sort = $request->input('sort', 'created_at');
                $order = $request->input('order', 'desc');
                $allowedSorts = ['id', 'name', 'company_name', 'email', 'phone', 'city', 'country_code', 'created_at'];

                if (in_array($sort, $allowedSorts, true)) {
                    $query->orderBy($sort, strtolower($order) === 'asc' ? 'asc' : 'desc');
                } else {
                    $query->latest();
                }

                $offset = (int) $request->input('offset', 0);
                $limit = (int) $request->input('limit', 10);
                $customers = $query->skip($offset)->take($limit)->get();

                return response()->json([
                    'total' => $total,
                    'rows' => $customers->map(fn (Customer $c) => $this->transformCustomer($c)),
                ]);
            }

            $customers = $query->latest()->get();

            return response()->json($customers->map(fn (Customer $c) => $this->transformCustomer($c)));
        }

        $customers = Customer::ownedBy($request->user()->id)
            ->search($request->input('q'))
            ->when(
                in_array($request->input('type'), Customer::CUSTOMER_TYPES, true),
                fn ($query) => $query->where('customer_type', $request->input('type'))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    protected function transformCustomer(Customer $customer): array
    {
        $badgeClass = $customer->customer_type === 'business' ? 'badge bg-primary' : 'badge bg-secondary';
        $typeLabel = $customer->customer_type === 'business' ? 'Business' : 'Individual';

        $nameHtml = '<div class="fw-semibold">' . e($customer->display_name) . '</div>';
        if ($customer->customer_type === 'business' && $customer->name) {
            $nameHtml .= '<small class="text-muted">' . e($customer->name) . '</small>';
        }

        $cityCountry = trim(implode(', ', array_filter([$customer->city, $customer->country_code])));

        $actionsHtml = '<div class="d-flex justify-content-end gap-2">'
            . '<a href="' . route('customers.show', $customer) . '" class="btn btn-sm btn-outline-primary" title="View" aria-label="View ' . e($customer->display_name) . '"><i class="mdi mdi-eye-outline"></i></a>'
            . '<a href="' . route('customers.edit', $customer) . '" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit ' . e($customer->display_name) . '"><i class="mdi mdi-pencil-outline"></i></a>'
            . '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete ' . e($customer->display_name) . '" data-delete-url="' . route('customers.destroy', $customer) . '" data-delete-name="' . e($customer->display_name) . '"><i class="mdi mdi-delete-outline"></i></button>'
            . '</div>';

        return [
            'id' => $customer->id,
            'name' => $nameHtml,
            'raw_name' => $customer->display_name,
            'customer_type' => '<span class="' . $badgeClass . '">' . $typeLabel . '</span>',
            'raw_type' => $customer->customer_type,
            'email' => $customer->email ?? '—',
            'phone' => $customer->phone ?? '—',
            'city_country' => $cityCountry ?: '—',
            'city' => $customer->city,
            'country_code' => $customer->country_code,
            'actions' => $actionsHtml,
        ];
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'customer' => new Customer(['customer_type' => 'individual']),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $request->user()->customers()->create($request->validated());

        return redirect()->route('customers.index')->with('status', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->route('customers.index')->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        // TODO: block deletion when the customer has invoices (activate when the invoices module is built).
        // if ($customer->invoices()->exists()) {
        //     return back()->with('status', 'This customer cannot be deleted because it has invoices.');
        // }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }
}
