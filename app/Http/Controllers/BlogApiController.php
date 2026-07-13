<?php
namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogApiController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('category')->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($blogs as $blog) {
            $data[] = [
                'id' => $blog->id,
                'title' => $blog->title,
                'description' => $blog->description,
                'image' => $blog->image ? url($blog->image) : null,
                'slug' => $blog->slug,
                'category' => $blog->category?->name ?? 'General',
                'created_at' => $blog->created_at,
            ];
        }
        return response()->json($data);
    }

    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)->with('category')->first();
        if (! $blog) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json([
            'id' => $blog->id,
            'title' => $blog->title,
            'description' => $blog->description,
            'image' => $blog->image ? url($blog->image) : null,
            'slug' => $blog->slug,
            'category' => $blog->category?->name ?? 'General',
            'meta_title' => $blog->meta_title,
            'meta_description' => $blog->meta_description,
            'meta_keywords' => $blog->meta_keywords,
            'created_at' => $blog->created_at,
        ]);
    }
}
