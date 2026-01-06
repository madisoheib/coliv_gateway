<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserWebhook;

class DashboardController extends Controller
{
    public function index()
    {
        $webhooks = UserWebhook::with('user')->orderBy('created_at', 'desc')->paginate(15);
        
        return view('admin.dashboard', compact('webhooks'));
    }
}
