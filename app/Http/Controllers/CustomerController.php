<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            $query = Customer::ownedBy($request->user()->id)
                ->search($request->input('search', $request->input('q')));

            $this->applyRequestSort($query, $request);

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
                ->map(fn (Customer $c) => $this->transformCustomer($c))
                ->values()
                ->all();

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
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

    protected function applyRequestSort(Builder $query, Request $request): void
    {
        $sortableColumns = [
            'id' => 'id',
            'name' => 'name',
            'company_name' => 'company_name',
            'customer_type' => 'customer_type',
            'email' => 'email',
            'phone' => 'phone',
            'city_country' => 'city',
            'created_at' => 'created_at',
        ];

        $sort = $sortableColumns[$request->input('sort', 'id')] ?? 'id';
        $order = strtolower((string) ($request->input('order', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $order)->orderBy('id', 'desc');
    }

    protected function transformCustomer(Customer $customer): array
    {
        $cityCountry = trim(implode(', ', array_filter([$customer->city, $customer->country_code])));

        return [
            'id' => $customer->id,
            'raw_name' => $customer->display_name,
            'company_name' => $customer->customer_type === 'business' ? ($customer->name ?? '') : '',
            'customer_type' => $customer->customer_type,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'city' => $customer->city,
            'country_code' => $customer->country_code,
            'city_country' => $cityCountry,
            'created_at' => optional($customer->created_at)->format('d M Y'),
            'show_url' => route('customers.show', $customer),
            'edit_url' => route('customers.edit', $customer),
            'delete_url' => route('customers.destroy', $customer),
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

        if ($customer->invoices()->exists()) {
            return back()->with('error', 'This customer cannot be deleted because they have invoices.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }
}
