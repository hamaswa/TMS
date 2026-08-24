<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TailoringWorkflowSettingController extends Controller
{
    public function edit(): View
    {
        return view('tailoring-workflow.edit', ['business' => $this->business()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tailoring_status_mode' => ['required', Rule::in(Business::TAILORING_STATUS_MODES)],
        ]);

        $this->business()->update($validated);

        return back()->with('success', 'سلائی کے کام کی حالتوں کی ترتیب محفوظ کر دی گئی ہے۔');
    }

    private function business(): Business
    {
        $user = Auth::user()->loadMissing(['business', 'ownedBusiness']);

        return $user->business ?? $user->ownedBusiness ?? abort(404);
    }
}
