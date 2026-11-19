<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('category')->orderBy('created_at', 'desc')->paginate(9);
        $categories = Category::all();
        return view('blogs.index', compact('blogs', 'categories'));
    }

    public function show($id)
    {
        $blog = Blog::with('category')->findOrFail($id);
        $relatedBlogs = Blog::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->limit(3)
            ->get();
        return view('blogs.show', compact('blog', 'relatedBlogs'));
    }

    public function byCategory($categoryId)
    {
        $category   = Category::findOrFail($categoryId);
        $blogs      = $category->blogs()->orderBy('created_at', 'desc')->paginate(9);
        $categories = Category::all();
        return view('blogs.index', compact('blogs', 'categories', 'category'));
    }

    // Admin Methods
    public function adminIndex()
    {
        $blogs = Blog::with('category', 'subCategory')->orderBy('created_at', 'desc')->paginate(10);
        $categories = Category::all();
        return view('blogs.admin.index', compact('blogs', 'categories'));
    }

    public function getBlogs()
    {
        $blogs = Blog::with('category', 'subCategory')->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach($blogs as $blog) {
            $data[] = [
                'title' => $blog->title,
                'category' => $blog->category->name,
                'image' => $blog->image ? (str_starts_with($blog->image, 'http') ? $blog->image : asset($blog->image)) : null,
                'id' => $blog->id,
                'sub_category_id' => $blog->sub_category_id
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getBlogById($id)
    {
        $blog = Blog::with('category', 'subCategory')->findOrFail($id);

        // Resolve image URL dynamically from whatever is stored:
        // - New records: stored as relative path  e.g. 'storage/blogs/file.png'
        // - Old records: stored as full URL e.g. 'http://127.0.0.1:8000/storage/blogs/file.png'
        $imageUrl = null;
        if ($blog->image) {

            if (str_starts_with($blog->image, 'http')) {
                // Legacy full URL — extract filename and rebuild with current server
                $filename = basename(parse_url($blog->image, PHP_URL_PATH));
                $imageUrl = asset('storage/blogs/' . $filename);
            } else {
                // Relative path — just wrap with asset()
                $imageUrl = asset($blog->image);
            }
            
        }

        return response()->json([
            'id'          => $blog->id,
            'title'       => $blog->title,
            'slug'        => $blog->slug,
            'category'    => $blog->category->name,
            'sub_category' => $blog->subCategory?->name,
            'image'       => $imageUrl,
            'description' => $blog->description,
            'meta_title' => $blog->meta_title,
            'meta_description' => $blog->meta_description,
            'meta_keywords' => $blog->meta_keywords,
        ]);
    }

    public function getSubCategories($categoryId)
    {
        $subCategories = SubCategory::where('category_id', $categoryId)->orderBy('order')->get();
        return response()->json($subCategories);
    }

    public function create()
    {
        $categories = Category::all();
        return view('blogs.admin.create', compact('categories'));
    }

    public function store(Request $request)
    {
        if ($request->has('sub_category_id') && empty($request->sub_category_id)) {
            $request->merge(['sub_category_id' => null]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if (empty($validated['sub_category_id'])) {
            $validated['sub_category_id'] = null;
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'public');
            // Store relative path so the URL works on any server/environment
            $validated['image'] = 'storage/' . $imagePath;
        }

        // Generate slug from title
        $validated['slug'] = $this->generateSlug($validated['title']);

        Blog::create($validated);
        return redirect()->route('blogs.admin.index')->with('success', 'Blog created successfully!');

    }

    public function edit($id)
    {
        $blog = Blog::with('subCategory')->findOrFail($id);
        $categories = Category::all();
        return view('blogs.admin.edit', compact('blog', 'categories'));

    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        if ($request->has('sub_category_id') && empty($request->sub_category_id)) {
            $request->merge(['sub_category_id' => null]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if (empty($validated['sub_category_id'])) {
            $validated['sub_category_id'] = null;
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'public');
            // Store relative path so the URL works on any server/environment
            $validated['image'] = 'storage/' . $imagePath;
        }

        // Generate slug from title if title changed
        if ($validated['title'] !== $blog->title) {
            $validated['slug'] = $this->generateSlug($validated['title']);
        }

        $blog->update($validated);
        return redirect()->route('blogs.admin.index')->with('success', 'Blog updated successfully!');
        
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('blogs.admin.index')->with('success', 'Blog deleted successfully!');
    }

    private function generateSlug($title)
    {
        $slug = \Illuminate\Support\Str::slug($title);
        if (empty($slug)) {
            $slug = 'blog-' . time();
        }
        
        // Check if slug exists and make it unique
        $originalSlug = $slug;
        $counter = 1;
        while (Blog::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

}
