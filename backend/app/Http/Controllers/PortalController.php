<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\Event;

class PortalController extends Controller
{
    public function clientPortal()
    {
        $client = Client::with(['events.venue', 'events.days.schedules', 'invoices.payments', 'quotations'])
            ->first();

        return view('portals.client', compact('client'));
    }

    public function vendorPortal()
    {
        $vendor = Vendor::with(['assignments.event', 'expenses.event', 'category'])
            ->first();

        return view('portals.vendor', compact('vendor'));
    }
}

