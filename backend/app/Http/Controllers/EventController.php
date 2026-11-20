<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Client;
use App\Models\Venue;
use App\Models\User;
use App\Models\EventDay;
use App\Models\EventSchedule;
use App\Models\EventTask;
use App\Models\Vendor;
use App\Models\VendorAssignment;
use App\Models\EventGuest;
use App\Models\GuestGroup;
use App\Models\EventBudget;
use App\Models\EventBudgetItem;
use App\Models\EventExpense;
use App\Models\TicketType;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['client', 'category', 'venue', 'manager']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('event_category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('event_code', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(12);
        $categories = EventCategory::all();

        return view('events.index', compact('events', 'categories'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $categories = EventCategory::all();
        $venues = Venue::where('status', 'available')->orderBy('name')->get();
        $managers = User::whereIn('role', ['event_manager', 'super_admin', 'company_admin', 'staff'])->get();

        return view('events.create', compact('clients', 'categories', 'venues', 'managers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'event_category_id' => 'required|exists:event_categories,id',
            'event_type' => 'required|string',
            'venue_id' => 'nullable|exists:venues,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'expected_guests' => 'nullable|integer|min:0',
            'estimated_budget' => 'nullable|numeric|min:0',
            'contract_value' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'theme_concept' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $eventCode = 'EVT-' . date('Y') . '-' . strtoupper(Str::random(5));

        $event = Event::create(array_merge($validated, [
            'company_id' => $companyId,
            'event_code' => $eventCode,
            'amount_received' => 0,
        ]));

        // Create first day automatically
        EventDay::create([
            'event_id' => $event->id,
            'day_number' => 1,
            'date' => Carbon::parse($validated['start_date'])->toDateString(),
            'title' => 'Day 1 Main Proceedings',
        ]);

        return redirect()->route('events.show', $event)->with('success', 'Event successfully created with code ' . $eventCode);
    }

    public function show(Event $event)
    {
        $event->load([
            'client',
            'category',
            'venue',
            'manager',
            'days.schedules',
            'schedules',
            'tasks.assignedUser',
            'vendorAssignments.vendor',
            'budget.items',
            'expenses.vendor',
            'guests.group',
            'ticketTypes.tickets',
            'invoices.payments',
            'cateringMenus',
            'decorationThemes',
        ]);

        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();
        $users = User::all();
        $guestGroups = GuestGroup::where('event_id', $event->id)->get();

        return view('events.show', compact('event', 'vendors', 'users', 'guestGroups'));
    }

    public function edit(Event $event)
    {
        $clients = Client::orderBy('name')->get();
        $categories = EventCategory::all();
        $venues = Venue::orderBy('name')->get();
        $managers = User::whereIn('role', ['event_manager', 'super_admin', 'company_admin', 'staff'])->get();

        return view('events.edit', compact('event', 'clients', 'categories', 'venues', 'managers'));
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'event_category_id' => 'required|exists:event_categories,id',
            'event_type' => 'required|string',
            'venue_id' => 'nullable|exists:venues,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'expected_guests' => 'nullable|integer|min:0',
            'confirmed_guests' => 'nullable|integer|min:0',
            'estimated_budget' => 'nullable|numeric|min:0',
            'actual_budget' => 'nullable|numeric|min:0',
            'contract_value' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'theme_concept' => 'nullable|string',
            'description' => 'nullable|string',
            'special_instructions' => 'nullable|string',
        ]);

        $event->update($validated);

        return redirect()->route('events.show', $event)->with('success', 'Event details updated successfully.');
    }

    public function updateStatus(Request $request, Event $event)
    {
        $request->validate(['status' => 'required|string']);
        $event->update(['status' => $request->status]);

        return back()->with('success', 'Event status transitioned to ' . ucfirst(str_replace('_', ' ', $request->status)));
    }

    public function addSchedule(Request $request, Event $event)
    {
        $validated = $request->validate([
            'event_day_id' => 'nullable|exists:event_days,id',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'title' => 'required|string|max:255',
            'location_within_venue' => 'nullable|string|max:255',
            'speaker_performer' => 'nullable|string|max:255',
            'activity_description' => 'nullable|string',
            'in_charge_person' => 'nullable|string|max:255',
        ]);

        $event->schedules()->create($validated);

        return back()->with('success', 'Run-of-show schedule item added.');
    }

    public function addTask(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'priority' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $event->tasks()->create(array_merge($validated, ['status' => 'pending']));

        return back()->with('success', 'Task added to event milestone list.');
    }

    public function toggleTask(EventTask $task)
    {
        $newStatus = ($task->status === 'completed') ? 'pending' : 'completed';
        $task->update(['status' => $newStatus]);

        return back()->with('success', 'Task status changed to ' . $newStatus);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('events.index')->with('success', 'Event removed.');
    }
}

