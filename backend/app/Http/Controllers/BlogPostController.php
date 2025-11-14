<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    public function index()
    {
        return response()->json(BlogPost::with('author')->latest()->get());
    }

    public function show($id)
    {
        $post = BlogPost::with('author')->find($id);
        if (!$post) {
            return response()->json(['message' => 'Blog post not found'], 404);
        }
        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'author_id' => 'required|exists:users,id',
            'image' => 'nullable|string',
            'published_at' => 'nullable|date',
        ]);
        $post = BlogPost::create($validated);
        return response()->json($post, 201);
    }

    public function update(Request $request, $id)
    {
        $post = BlogPost::find($id);
        if (!$post) {
            return response()->json(['message' => 'Blog post not found'], 404);
        }
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'author_id' => 'sometimes|required|exists:users,id',
            'image' => 'nullable|string',
            'published_at' => 'nullable|date',
        ]);
        $post->update($validated);
        return response()->json($post);
    }

    public function destroy($id)
    {
        $post = BlogPost::find($id);
        if (!$post) {
            return response()->json(['message' => 'Blog post not found'], 404);
        }
        $post->delete();
        return response()->json(['message' => 'Blog post deleted']);
    }
}
