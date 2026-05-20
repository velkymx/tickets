<?php

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\AutomationAction;
use App\Models\AutomationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->isAdmin()) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        $automations = Automation::query()
            ->withCount('runs')
            ->latest()
            ->paginate(20);

        return view('automations.index', compact('automations'));
    }

    public function create(): View
    {
        $triggers = config('automations.triggers');
        $operators = config('automations.operators');
        $actionTypes = config('automations.action_types');

        return view('automations.create', compact('triggers', 'operators', 'actionTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger' => 'required|string|in:' . implode(',', array_keys(config('automations.triggers'))),
            'conditions_json' => 'nullable|string',
            'actions_json' => 'nullable|string',
        ]);

        $automation = Automation::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger' => $validated['trigger'],
            'enabled' => $request->boolean('enabled', true),
            'stop_after_match' => $request->boolean('stop_after_match', true),
            'created_by' => auth()->id(),
        ]);

        $conditions = json_decode($validated['conditions_json'] ?? '{"all":[]}', true) ?? ['all' => []];
        $actions = json_decode($validated['actions_json'] ?? '[]', true) ?? [];
        $this->syncRules($automation, $conditions, $actions);

        return redirect()->route('automations.index')->with('success', 'Automation created.');
    }

    public function edit(Automation $automation): View
    {
        $automation->load('rules.actions');
        $triggers = config('automations.triggers');
        $operators = config('automations.operators');
        $actionTypes = config('automations.action_types');

        return view('automations.edit', compact('automation', 'triggers', 'operators', 'actionTypes'));
    }

    public function update(Request $request, Automation $automation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger' => 'required|string|in:' . implode(',', array_keys(config('automations.triggers'))),
            'conditions_json' => 'nullable|string',
            'actions_json' => 'nullable|string',
        ]);

        $automation->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger' => $validated['trigger'],
            'enabled' => $request->boolean('enabled', true),
            'stop_after_match' => $request->boolean('stop_after_match', true),
        ]);

        $conditions = json_decode($validated['conditions_json'] ?? '{"all":[]}', true) ?? ['all' => []];
        $actions = json_decode($validated['actions_json'] ?? '[]', true) ?? [];
        $automation->rules()->delete();
        $this->syncRules($automation, $conditions, $actions);

        return redirect()->route('automations.index')->with('success', 'Automation updated.');
    }

    public function show(Automation $automation): View
    {
        $runs = $automation->runs()
            ->with('actionLogs')
            ->latest()
            ->paginate(30);

        return view('automations.show', compact('automation', 'runs'));
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        $automation->delete();

        return redirect()->route('automations.index')->with('success', 'Automation deleted.');
    }

    public function fields(string $trigger): \Illuminate\Http\JsonResponse
    {
        $fields = [
            'ticket.created' => [
                ['key' => 'ticket.id', 'label' => 'Ticket ID', 'type' => 'integer'],
                ['key' => 'ticket.subject', 'label' => 'Subject', 'type' => 'string'],
                ['key' => 'ticket.status', 'label' => 'Status', 'type' => 'string'],
                ['key' => 'ticket.priority', 'label' => 'Priority', 'type' => 'string'],
                ['key' => 'ticket.type', 'label' => 'Type', 'type' => 'string'],
                ['key' => 'ticket.project', 'label' => 'Project', 'type' => 'string'],
                ['key' => 'actor.email', 'label' => 'Actor Email', 'type' => 'string'],
            ],
            'ticket.updated' => [
                ['key' => 'ticket.id', 'label' => 'Ticket ID', 'type' => 'integer'],
                ['key' => 'ticket.subject', 'label' => 'Subject', 'type' => 'string'],
                ['key' => 'ticket.status', 'label' => 'Status', 'type' => 'string'],
                ['key' => 'ticket.priority', 'label' => 'Priority', 'type' => 'string'],
                ['key' => 'ticket.type', 'label' => 'Type', 'type' => 'string'],
                ['key' => 'ticket.project', 'label' => 'Project', 'type' => 'string'],
                ['key' => 'actor.email', 'label' => 'Actor Email', 'type' => 'string'],
                ['key' => 'status_id', 'label' => 'Status (changed)', 'type' => 'changed'],
                ['key' => 'importance_id', 'label' => 'Priority (changed)', 'type' => 'changed'],
                ['key' => 'user_id2', 'label' => 'Assignee (changed)', 'type' => 'changed'],
            ],
        ];

        return response()->json($fields[$trigger] ?? []);
    }

    private function syncRules(Automation $automation, array $conditions, array $actions): void
    {
        $rule = AutomationRule::create([
            'automation_id' => $automation->id,
            'sort_order' => 0,
            'conditions_json' => $conditions ?: ['all' => []],
            'continue_processing' => false,
        ]);

        foreach ($actions as $sortOrder => $actionData) {
            AutomationAction::create([
                'automation_rule_id' => $rule->id,
                'type' => $actionData['type'] ?? 'call_webhook',
                'sort_order' => $sortOrder,
                'config_json' => $actionData['config'] ?? [],
            ]);
        }
    }
}
