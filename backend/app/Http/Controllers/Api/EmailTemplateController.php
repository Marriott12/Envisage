<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    // Get all email templates
    public function index(Request $request)
    {
        $query = EmailTemplate::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($templates);
    }

    // Get template details
    public function show($id)
    {
        $template = EmailTemplate::findOrFail($id);

        return response()->json($template);
    }

    // Create template
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|in:transactional,marketing,automation',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::create($request->all());

        return response()->json([
            'message' => 'Email template created successfully',
            'template' => $template,
        ], 201);
    }

    // Update template
    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'subject' => 'string|max:255',
            'body' => 'string',
            'type' => 'in:transactional,marketing,automation',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($request->all());

        return response()->json([
            'message' => 'Email template updated successfully',
            'template' => $template,
        ]);
    }

    // Delete template
    public function destroy($id)
    {
        $template = EmailTemplate::findOrFail($id);

        // Check if template is used in campaigns
        if ($template->campaigns()->exists()) {
            return response()->json([
                'error' => 'Cannot delete template that is used in campaigns',
            ], 400);
        }

        $template->delete();

        return response()->json(['message' => 'Email template deleted successfully']);
    }

    // Preview template with test data
    public function preview(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $request->validate([
            'variables' => 'required|array',
        ]);

        $rendered = $template->render($request->variables);

        return response()->json($rendered);
    }

    // Duplicate template
    public function duplicate($id)
    {
        $template = EmailTemplate::findOrFail($id);

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->save();

        return response()->json([
            'message' => 'Template duplicated successfully',
            'template' => $newTemplate,
        ]);
    }
}
