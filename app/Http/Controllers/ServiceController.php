<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View { return view('services.index', ['services' => Service::orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse { Service::create($this->validated($request)); return back()->with('success', 'Service saved.'); }
    public function update(Request $request, Service $service): RedirectResponse { $service->update($this->validated($request)); return back()->with('success', 'Service updated.'); }
    public function destroy(Service $service): RedirectResponse { $service->delete(); return back()->with('success', 'Service deleted.'); }
    private function validated(Request $request): array { return $request->validate(['name' => ['required','string','max:255'], 'description' => ['nullable','string'], 'price' => ['required','numeric','min:0']]); }
}
