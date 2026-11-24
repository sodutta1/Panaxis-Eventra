<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
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
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Event::with(['client', 'category', 'venue', 'manager']), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('event_category_id', $request->category_id);
        }


        return $this->listResponse($query, $request, EventResource::class);
    }

    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();
        $eventCode = 'EVT-'.date('Y').'-'.strtoupper(Str::random(5));

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
            Event::with(array_merge(['client', 'category', 'venue', 'manager'], $request->boolean('summary') ? [] : ['schedules','tasks.assignedUser'])),
            $request,
            $event
        ));
    }

    public function update(UpdateEventRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);

        if ($request->has('status') && ! $model->canTransitionTo($request->status)) {
            return response()->json([
                'message' => "Cannot move an event from '{$model->status}' to '{$request->status}'.",
                'errors' => ['status' => ["Transition from '{$model->status}' to '{$request->status}' is not allowed."]],
            ], 422);
        }

        $model->update($request->validated());

        return new EventResource($model->fresh(['client', 'category', 'venue', 'manager']));
    }

    public function destroy(Request $request, int $event)
    {
        /** @var Event $model */
        $model = $this->findForCompany(Event::query(), $request, $event);

        // Deleting an event cascade-deletes its guests, tickets, budget items
        // and work orders, which would silently erase gate, sales and vendor
        // payment records. Refuse instead, same contract as client/invoice deletes.
        if ($model->guests()->exists()
            || $model->tickets()->exists()
            || $model->vendorAssignments()->exists()
            || ($model->budget && $model->budget->items()->exists())) {
            return response()->json([
                'message' => 'This event has guests, tickets, budget items or work orders and cannot be deleted. Delete is blocked to protect event and financial records.',
            ], 409);
        }

        $model->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    public function updateStatus(UpdateEventStatusRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);

        if (! $model->canTransitionTo($request->status)) {
            return response()->json([
                'message' => "Cannot move an event from '{$model->status}' to '{$request->status}'.",
                'errors' => ['status' => ["Transition from '{$model->status}' to '{$request->status}' is not allowed."]],
            ], 422);
        }

        $model->update(['status' => $request->status]);

        return new EventResource($model->fresh());
    }

    public function addSchedule(StoreScheduleRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $data = $request->validated();

        // A schedule's date is persisted through its event day. Resolve the
        // day explicitly instead of silently dropping schedule_date.
        $day = null;
        if (! empty($data['event_day_id'])) {
            $day = $model->days()->where('id', $data['event_day_id'])->first();
            if (! $day) {
                throw ValidationException::withMessages([
                    'event_day_id' => 'This day does not belong to the event.',
                ]);
            }
        }
        if (! empty($data['schedule_date'])) {
            $byDate = $model->days()->where('date', $data['schedule_date'])->first();
            if (! $byDate) {
                throw ValidationException::withMessages([
                    'schedule_date' => 'The event has no day on this date.',
                ]);
            }
            if ($day && $day->id !== $byDate->id) {
                throw ValidationException::withMessages([
                    'schedule_date' => 'The schedule_date does not match the given event_day_id.',
                ]);
            }
            $day = $byDate;
        }
        unset($data['schedule_date']);
        $data['event_day_id'] = $day?->id;

        $schedule = $model->schedules()->create($data);

        return (new ScheduleResource($schedule))->response()->setStatusCode(201);
    }

    /** Tasks for one event, grouped by phase (pre_event / on_event / post_event). */
    public function stream(Request $request,int $event,string $section) {
        $model=$this->findForCompany(Event::query(),$request,$event);
        abort_unless(in_array($section,['schedules','tasks']),404);
        $q=$model->$section();if($section==='tasks')$q->with('assignedUser');
        $p=$q->orderBy('id')->paginate(20);
        $resource=$section==='tasks'?TaskResource::class:\App\Http\Resources\V1\ScheduleResource::class;
        return $resource::collection($p);
    }

    public function tasksIndex(Request $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $tasks = $model->tasks()->with('assignedUser')->orderBy('due_date')->get();

        $grouped = collect(EventTask::PHASES)->mapWithKeys(fn ($phase) => [
            $phase => TaskResource::collection($tasks->where('phase', $phase)->values()),
        ]);

        return response()->json(['data' => $grouped]);
    }

    public function addTask(StoreTaskRequest $request, int $event)
    {
        $model = $this->findForCompany(Event::query(), $request, $event);
        $data = array_merge($request->validated(), ['status' => 'pending', 'phase' => $request->input('phase', 'pre_event')]);

        if (! empty($data['depends_on'])) {
            $dependency = $model->tasks()->find($data['depends_on']);
            if (! $dependency) {
                throw ValidationException::withMessages(['depends_on' => 'The dependency must be a task of the same event.']);
            }
        }

        $task = $model->tasks()->create($data);

        return (new TaskResource($task->load('assignedUser')))->response()->setStatusCode(201);
    }

    public function updateTask(StoreTaskRequest $request, int $task)
    {
        $model = $this->findTaskForCompany($request, $task);
        $data = $request->validated();

        if (! empty($data['depends_on'])) {
            if ((int) $data['depends_on'] === $model->id) {
                throw ValidationException::withMessages(['depends_on' => 'A task cannot depend on itself.']);
            }
            $dependency = $model->event->tasks()->find($data['depends_on']);
            if (! $dependency) {
                throw ValidationException::withMessages(['depends_on' => 'The dependency must be a task of the same event.']);
            }
            if ($this->createsDependencyCycle($model, $dependency)) {
                throw ValidationException::withMessages(['depends_on' => 'This would create a circular dependency.']);
            }
        }

        $model->update($data);

        return new TaskResource($model->fresh(['assignedUser']));
    }

    private function createsDependencyCycle(EventTask $task, EventTask $dependency): bool
    {
        $seen = [$task->id => true];
        $current = $dependency;
        while ($current) {
            if (isset($seen[$current->id])) {
                return true;
            }
            $seen[$current->id] = true;
            $current = $current->dependency;
        }

        return false;
    }

    private function findTaskForCompany(Request $request, int $task): EventTask
    {
        $model = EventTask::whereHas('event', fn ($q) => $q->where('company_id', $this->companyId($request)))->find($task);
        abort_unless($model, 404, 'Resource not found.');

        return $model;
    }

    public function toggleTask(Request $request, int $task)
    {
        $companyId = $this->companyId($request);
        $model = EventTask::whereHas('event', fn ($q) => $q->where('company_id', $companyId))->find($task);

        if (! $model) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }

        if ($model->status !== 'completed' && $model->isBlocked()) {
            throw ValidationException::withMessages([
                'status' => 'This task is blocked by "'.$model->dependency->title.'", which is not completed yet.',
            ]);
        }

        $model->update(['status' => $model->status === 'completed' ? 'pending' : 'completed']);

        return new TaskResource($model->fresh());
    }
}
