<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Runovia\ApiSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * GET /welcome/business
 */
class CreateBusinessFormController extends Controller
{
    public function __construct(private readonly ApiSession $session)
    {
    }

    public function __invoke(): View|RedirectResponse
    {
        /*
         * ! Guarded here as well as by EnsureBusinessExists, because this route
         *   deliberately does NOT carry that middleware — it cannot, or a user with no
         *   business would be redirected to it in a loop. Without this check, someone who
         *   already has a business could reach the form from browser history and submit it,
         *   only to be refused by the API with a 409.
         */
        if ($this->session->hasBusiness()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.business');
    }
}
