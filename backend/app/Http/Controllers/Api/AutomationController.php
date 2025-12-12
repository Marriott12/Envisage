<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\AutomationExecution;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    // Get all automation rules
    public function index(Request $request)
    {
        $rules = AutomationRule::withCount('executions')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($rules);
    }

    // Get automation rule details
    public function show($id)
    {
        $rule = AutomationRule::with('executions')->findOrFail($id);

        return response()->json($rule);
    }

    // Create automation rule
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|string',
            'conditions' => 'nullable|array',
            'actions' => 'required|array',
            'delay_minutes' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $rule = AutomationRule::create($request->all());

        return response()->json([
            'message' => 'Automation rule created successfully',
            'rule' => $rule,
        ], 201);
    }

    // Update automation rule
    public function update(Request $request, $id)
    {
        $rule = AutomationRule::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'trigger' => 'string',
            'conditions' => 'nullable|array',
            'actions' => 'array',
            'delay_minutes' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $rule->update($request->all());

        return response()->json([
            'message' => 'Automation rule updated successfully',
            'rule' => $rule,
        ]);
    }

    // Delete automation rule
    public function destroy($id)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Automation rule deleted successfully']);
    }

    // Toggle automation rule
    public function toggle($id)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'message' => 'Automation rule toggled successfully',
            'is_active' => $rule->is_active,
        ]);
    }

    // Get automation execution history
    public function executions($id)
    {
        $rule = AutomationRule::findOrFail($id);
        
        $executions = $rule->executions()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(50);

        return response()->json($executions);
    }

    // Trigger automation manually
    public function trigger(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'data' => 'nullable|array',
        ]);

        $rule = AutomationRule::findOrFail($id);

        if (!$rule->is_active) {
            return response()->json(['error' => 'Automation rule is not active'], 400);
        }

        $execution = AutomationExecution::create([
            'rule_id' => $rule->id,
            'user_id' => $request->user_id,
            'status' => 'pending',
            'data' => $request->data,
            'scheduled_at' => now()->addMinutes($rule->delay_minutes),
        ]);

        return response()->json([
            'message' => 'Automation triggered successfully',
            'execution' => $execution,
        ]);
    }

    // Get automation statistics
    public function stats()
    {
        $stats = [
            'total_rules' => AutomationRule::count(),
            'active_rules' => AutomationRule::where('is_active', true)->count(),
            'total_executions' => AutomationExecution::count(),
            'executed_today' => AutomationExecution::whereDate('executed_at', today())->count(),
            'pending' => AutomationExecution::where('status', 'pending')->count(),
            'failed' => AutomationExecution::where('status', 'failed')->count(),
            'top_triggers' => AutomationRule::selectRaw('trigger, COUNT(*) as count')
                ->groupBy('trigger')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}
