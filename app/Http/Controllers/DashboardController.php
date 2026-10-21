<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboards)
    {
    }

    public function __invoke(Request $request): View
    {
        return view('dashboard', $this->dashboards->build($request->user()->id));
    }

    /**
     * Statement PDF: every invoice and payment, grouped by currency.
     */
    public function statement(Request $request): Response
    {
        $data = $this->dashboards->statement($request->user()->id);

        return Pdf::loadView('pdf.statement', [
            'user' => $request->user(),
            'currencyGroups' => $data['currencyGroups'],
            'generatedAt' => now(),
        ])->stream('invoice-statement.pdf');
    }
}
