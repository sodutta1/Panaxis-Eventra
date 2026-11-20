<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreEventRequest;
use App\Http\Requests\V1\StoreScheduleRequest;
use App\Http\Requests\V1\StoreTaskRequest;
use App\Http\Requests\V1\UpdateEventRequest;
use App\Http\Requests\V1\UpdateEventStatusRequest;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\ScheduleResource;
use App\Http\Resources\V1\TaskResource;
use App\Models\Event;
use App\Models\EventDay;
use App\Models\EventTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Event::with(['client', 'category', 'venue', 'manager']), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('event_category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('event_code', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        return EventResource::collection($query->orderBy('start_date', 'desc')->paginate(12));
    }

    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();
        $eventCode = 'EVT-' . date('Y') . '-' . strtoupper(Str::random(5));

        $event = Event::create(array_merge($validated, [
            'company_id' => $this->companyId($request),
            'event_code' => $eventCode,
            'amount_received' => 0,
        ]));

        // Mirrors the web controller: first event day is created automatically
        EventDay::create([
            'event_id' => $event->id,
            'day_number' => 1,
            'date' => Carbon::parse($validated['start_date'])->toDateString(),
            'title' => 'Day 1 Main Proceedings',
        ]);

        return (new EventResource($event->load(['client', 'category', 'venue', 'manager'])))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $event)
    {
        return new EventResource($this->findForCompany(
            Event::with(['client', 'category', 'venue', 'manager', 'schedules', 'tasks.assignedUser']),
            $request,
            $event
        ));
    }

    public function update(UpdateEventRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $model->update($request->validated());

        return new EventResource($model->fresh(['client', 'category', 'venue', 'manager']));
    }

    public function destroy(Request $request, int $event)
    {
        $this->findForCompany(Event::query(), $request, $event)->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    public function updateStatus(UpdateEventStatusRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $model->update(['status' => $request->status]);

        return new EventResource($model->fresh());
    }

    public function addSchedule(StoreScheduleRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $schedule = $model->schedules()->create($request->validated());

        return (new ScheduleResource($schedule))->response()->setStatusCode(201);
    }

    public function addTask(StoreTaskRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $task = $model->tasks()->create(array_merge($request->validated(), ['status' => 'pending']));

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function toggleTask(Request $request, int $task)
    {
        $companyId = $this->companyId($request);
        $model = EventTask::whereHas('event', fn ($q) => $q->where('company_id', $companyId))->find($task);

        if (!$model) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }

        $model->update(['status' => $model->status === 'completed' ? 'pending' : 'completed']);

        return new TaskResource($model->fresh());
    }
}
