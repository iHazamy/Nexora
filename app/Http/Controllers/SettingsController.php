<?php
namespace App\Http\Controllers;
use App\Models\BankAccount;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class SettingsController extends Controller {
 public function edit(): View { return view('settings.edit', ['settings' => Setting::values(), 'bankAccounts' => BankAccount::orderBy('bank_name')->get()]); }
 public function update(Request $request): RedirectResponse { $data=$request->validate(['company_name'=>['nullable','string','max:255'],'address'=>['nullable','string'],'phone'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],'registration_number'=>['nullable','string','max:100'],'terms'=>['nullable','string'],'logo'=>['nullable','image','max:2048']]); $logo = $request->hasFile('logo') ? $request->file('logo')->store('logos','public') : null; unset($data['logo']); foreach($data as $key=>$value) Setting::updateOrCreate(['key'=>$key],['value'=>$value]); if($logo) Setting::updateOrCreate(['key'=>'logo'],['value'=>$logo]); return back()->with('success','Settings saved.'); }
}
