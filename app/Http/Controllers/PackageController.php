<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(): View { return view('packages.index', ['packages' => Package::latest()->get()]); }
    public function store(Request $request): RedirectResponse { Package::create($this->validated($request)); return back()->with('success', 'Package saved.'); }
    public function update(Request $request, Package $package): RedirectResponse { $package->update($this->validated($request)); return back()->with('success', 'Package updated.'); }
    public function destroy(Package $package): RedirectResponse { $package->delete(); return back()->with('success', 'Package deleted.'); }
    private function validated(Request $request): array { return $request->validate(['name' => ['required','string','max:255'], 'description' => ['nullable','string'], 'price' => ['required','numeric','min:0']]); }
}
