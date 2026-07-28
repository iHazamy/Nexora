<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use Illuminate\View\View;
class DashboardController extends Controller { public function __invoke(): View { return view('dashboard', ['count' => Invoice::count(), 'sales' => Invoice::sum('grand_total'), 'outstanding' => Invoice::sum('balance'), 'recent' => Invoice::latest()->take(5)->get()]); } }
