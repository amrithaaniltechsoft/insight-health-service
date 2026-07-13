<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function adminIndex()
    {
        $categories = Category::orderBy('id')->get();
        $faqs = Faq::with('category', 'subCategory')->orderBy('created_at', 'desc')->get();
        return view('faqs.admin.index', compact('categories', 'faqs'));
    }

    public function getFaqs()
    {
        $faqs = Faq::with('category', 'subCategory')->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($faqs as $faq) {
            $data[] = [
                'id' => $faq->id,
                'category' => $faq->category?->name ?? 'N/A' . ($faq->subCategory ? ' / ' . $faq->subCategory->name : ''),
                'question' => $faq->question,
                'sub_category_id' => $faq->sub_category_id,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getFaqById($id)
    {
        $faq = Faq::with('category', 'subCategory')->findOrFail($id);
        return response()->json([
            'id' => $faq->id,
            'category_id' => $faq->category_id,
            'category' => $faq->category?->name ?? 'N/A',
            'sub_category' => $faq->subCategory?->name ?? null,
            'sub_category_id' => $faq->sub_category_id,
            'question' => $faq->question,
            'answer' => $faq->answer,
        ]);
    }

    public function getPublicFaqs()
    {
        $faqs = Faq::with('category')->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($faqs as $faq) {
            $data[] = [
                'q' => $faq->question,
                'a' => $faq->answer,
                'category' => $faq->category?->name ?? 'General',
            ];
        }
        return response()->json($data);
    }

    public function getPublicFaqsBySlug(string $slug)
    {
        $category = \App\Models\Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json([]);
        }

        $faqs = Faq::where('category_id', $category->id)
            ->orderBy('order')
            ->orderBy('created_at')
            ->get();

        return response()->json(
            $faqs->map(fn($faq) => [
                'q' => $faq->question,
                'a' => $faq->answer,
            ])
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'questions' => 'required|array|min:1',
            'questions.*' => 'required|string|max:255',
            'answers' => 'required|array|min:1',
            'answers.*' => 'required|string',
        ]);

        foreach ($validated['questions'] as $i => $question) {
            Faq::create([
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'question' => $question,
                'answer' => $validated['answers'][$i] ?? '',
            ]);
        }

        return redirect()->back()->with('success', count($validated['questions']) . ' FAQ(s) created successfully!');
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq->update($validated);
        return redirect()->route('faqs.admin.index')->with('success', 'FAQ updated successfully!');
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('faqs.admin.index')->with('success', 'FAQ deleted successfully!');
    }
}
