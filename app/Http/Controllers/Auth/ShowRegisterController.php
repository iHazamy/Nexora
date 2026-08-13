<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * GET /register
 */
class ShowRegisterController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.register');
    }
}
