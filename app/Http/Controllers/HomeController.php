<?php

namespace App\Http\Controllers;

use App\Support\Lookups;
use Illuminate\Contracts\View\View;

/**
 * الصفحة الرئيسية: ترحيب + كل أقسام اللوحة.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', ['navGroups' => Lookups::navGroups()]);
    }
}
