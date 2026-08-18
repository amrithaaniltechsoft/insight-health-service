<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
                'id'              => $faq->id,
                'category'        => $faq->category?->name ?? 'N/A' . ($faq->subCategory ? ' / ' . $faq->subCategory->name : ''),
                'question'        => $faq->question,
                'sub_category_id' => $faq->sub_category_id,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getFaqById($id)
    {
        $faq = Faq::with('category', 'subCategory')->findOrFail($id);
        return response()->json([
            'id'              => $faq->id,
            'category_id'     => $faq->category_id,
            'category'        => $faq->category?->name ?? 'N/A',
            'sub_category'    => $faq->subCategory?->name ?? null,
            'sub_category_id' => $faq->sub_category_id,
            'question'        => $faq->question,
            'answer'          => $faq->answer,
        ]);
    }

    public function getPublicFaqs()
    {
        $faqs = Faq::with('category', 'subCategory')->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($faqs as $faq) {
            $data[] = [
                'q'            => $faq->question,
                'a'            => $faq->answer,
                'category'     => $faq->category?->name ?? 'General',
                'sub_category' => $faq->subCategory?->name ?? null,
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
            'category_id'     => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'questions'       => 'required|array|min:1',
            'questions.*'     => 'required|string|max:255',
            'answers'         => 'required|array|min:1',
            'answers.*'       => 'required|string',
        ]);

        foreach ($validated['questions'] as $i => $question) {
            Faq::create([
                'category_id'     => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'question'        => $question,
                'answer'          => $validated['answers'][$i] ?? '',
            ]);
        }

        return redirect()->back()->with('success', count($validated['questions']) . ' FAQ(s) created successfully!');
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'category_id'     => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'question'        => 'required|string|max:255',
            'answer'          => 'required|string',
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

    // ─────────────────────────────────────────────────────────────────────────
    // IMPORT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Download a sample CSV template for FAQ import.
     */
    public function downloadSample()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="faq_import_sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['category', 'sub_category', 'question', 'answer']);
            fputcsv($handle, ['Pregnancy Scans', 'Early Reassurance Scan', 'What is included in the Early Pregnancy Scan?', 'The scan includes a viability check, heartbeat confirmation, and a written report.']);
            fputcsv($handle, ['Diagnostics', '', 'Do I need a referral for an ultrasound?', 'No referral is required. You can book directly with us.']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import FAQs from an uploaded Excel (.xlsx/.xls) or CSV file.
     *
     * Expected columns (case-insensitive, any order):
     *   category (or category_slug/category_name) | sub_category (optional) | question | answer
     */
    public function importFaqs(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $defaultCategoryId = $request->input('category_id');
        $file              = $request->file('import_file');
        $extension         = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'csv' || $extension === 'txt') {
                $reader = IOFactory::createReader('Csv');
                $reader->setDelimiter(',');
                $spreadsheet = $reader->load($file->getPathname());
            } else {
                $spreadsheet = IOFactory::load($file->getPathname());
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Could not read file: ' . $e->getMessage());
        }

        $sheet    = $spreadsheet->getActiveSheet();
        $rows     = $sheet->toArray(null, true, true, true); // keyed by column letter A, B, …
        $header   = null;
        $colMap   = [];
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        // Cache categories and sub-categories to avoid repeated queries
        $allCategories            = Category::all();
        $subCategoriesPerCategory = [];
        $categoryCache            = [];
        $subCategoryCache         = [];

        foreach ($rows as $rowIndex => $row) {
            // ── First row = header ────────────────────────────────────────────
            if ($header === null) {
                foreach ($row as $col => $val) {
                    if ($val !== null && trim($val) !== '') {
                        $rawKey = strtolower(trim($val));
                        $normalizedKey = str_replace([' ', '-'], '_', $rawKey);

                        if (in_array($normalizedKey, ['category', 'category_name', 'category_slug', 'cat', 'cat_name'])) {
                            $colMap['category'] = $col;
                        } elseif (in_array($normalizedKey, ['sub_category', 'subcategory', 'sub_category_name', 'subcat'])) {
                            $colMap['sub_category'] = $col;
                        } elseif (in_array($normalizedKey, ['question', 'questions', 'q'])) {
                            $colMap['question'] = $col;
                        } elseif (in_array($normalizedKey, ['answer', 'answers', 'a'])) {
                            $colMap['answer'] = $col;
                        }
                    }
                }
                $header = $colMap;

                if (!isset($colMap['question']) || !isset($colMap['answer'])) {
                    return redirect()->back()->with('error',
                        'Invalid file format. Required columns: question, answer'
                    );
                }

                if (!isset($colMap['category']) && !$defaultCategoryId) {
                    return redirect()->back()->with('error',
                        'Invalid file format. Please include a Category column in your file or select a Default Category in the form.'
                    );
                }
                continue;
            }

            // ── Data rows ────────────────────────────────────────────────────
            $catInput   = isset($colMap['category']) ? trim($row[$colMap['category']] ?? '') : '';
            $subCatName = isset($colMap['sub_category']) ? trim($row[$colMap['sub_category']] ?? '') : '';
            $question   = trim($row[$colMap['question']] ?? '');
            $answer     = trim($row[$colMap['answer']] ?? '');

            // Skip blank rows
            if ($catInput === '' && $question === '' && $answer === '') {
                continue;
            }

            if ($question === '' || $answer === '') {
                $skipped++;
                $errors[] = "Row {$rowIndex}: missing question or answer.";
                continue;
            }

            // Resolve category using smart similarity matching
            $categoryId = null;
            if ($catInput !== '') {
                if (!array_key_exists($catInput, $categoryCache)) {
                    $matchedCat = $this->findBestMatchingCategory($catInput, $allCategories);
                    $categoryCache[$catInput] = $matchedCat?->id;
                }
                $categoryId = $categoryCache[$catInput];
            }

            // Fall back to default selected category if row category didn't match
            if (!$categoryId && $defaultCategoryId) {
                $categoryId = $defaultCategoryId;
            }

            if (!$categoryId) {
                $skipped++;
                $errors[] = "Row {$rowIndex}: category '{$catInput}' not found.";
                continue;
            }

            // Resolve optional sub-category using smart similarity matching
            $subCategoryId = null;
            if ($subCatName !== '') {
                $cacheKey = $categoryId . '|' . strtolower($subCatName);
                if (!array_key_exists($cacheKey, $subCategoryCache)) {
                    if (!isset($subCategoriesPerCategory[$categoryId])) {
                        $subCategoriesPerCategory[$categoryId] = SubCategory::where('category_id', $categoryId)->get();
                    }
                    $matchedSubCat = $this->findBestMatchingSubCategory($subCatName, $subCategoriesPerCategory[$categoryId]);
                    $subCategoryCache[$cacheKey] = $matchedSubCat?->id;
                }
                $subCategoryId = $subCategoryCache[$cacheKey];
            }

            Faq::create([
                'category_id'     => $categoryId,
                'sub_category_id' => $subCategoryId,
                'question'        => $question,
                'answer'          => $answer,
            ]);
            $imported++;
        }

        $message = "{$imported} FAQ(s) imported successfully!";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped. Details: " . implode(' | ', array_slice($errors, 0, 5));
        }

        return redirect()->route('faqs.admin.index')->with('success', $message);
    }

    /**
     * Resolve existing Category by exact match, substring match, or similarity score.
     */
    private function findBestMatchingCategory(string $input, $allCategories): ?Category
    {
        $inputTrimmed = trim($input);
        if ($inputTrimmed === '') {
            return null;
        }

        $inputLower = mb_strtolower($inputTrimmed);
        $inputSlug  = \Illuminate\Support\Str::slug($inputTrimmed);

        // 1. Exact match (ID, Name, Slug, Slugified Name)
        foreach ($allCategories as $category) {
            $catNameLower = mb_strtolower($category->name);
            $catSlugLower = mb_strtolower($category->slug);

            if ((string)$category->id === $inputTrimmed ||
                $catNameLower === $inputLower ||
                $catSlugLower === $inputLower ||
                $catSlugLower === $inputSlug) {
                return $category;
            }
        }

        // 2. Substring / Contains match
        foreach ($allCategories as $category) {
            $catNameLower = mb_strtolower($category->name);
            $catSlugLower = mb_strtolower($category->slug);

            if (str_contains($catNameLower, $inputLower) || str_contains($inputLower, $catNameLower)) {
                return $category;
            }

            if ($inputSlug !== '' && (str_contains($catSlugLower, $inputSlug) || str_contains($inputSlug, $catSlugLower))) {
                return $category;
            }
        }

        // 3. Similarity / Fuzzy matching (similar_text)
        $bestMatch   = null;
        $highestPerc = 0;

        foreach ($allCategories as $category) {
            $catNameLower = mb_strtolower($category->name);
            similar_text($inputLower, $catNameLower, $percentName);

            $catSlugLower = mb_strtolower($category->slug);
            similar_text($inputLower, $catSlugLower, $percentSlug);

            $maxPercent = max($percentName, $percentSlug);

            if ($maxPercent > $highestPerc) {
                $highestPerc = $maxPercent;
                $bestMatch   = $category;
            }
        }

        if ($highestPerc >= 45.0) {
            return $bestMatch;
        }

        return null;
    }

    /**
     * Resolve existing SubCategory under a Category by exact match, substring match, or similarity score.
     */
    private function findBestMatchingSubCategory(string $input, $subCategories): ?SubCategory
    {
        $inputTrimmed = trim($input);
        if ($inputTrimmed === '') {
            return null;
        }

        $inputLower = mb_strtolower($inputTrimmed);

        // 1. Exact match
        foreach ($subCategories as $subCat) {
            $subNameLower = mb_strtolower($subCat->name);
            if ((string)$subCat->id === $inputTrimmed || $subNameLower === $inputLower) {
                return $subCat;
            }
        }

        // 2. Substring / Contains match
        foreach ($subCategories as $subCat) {
            $subNameLower = mb_strtolower($subCat->name);
            if (str_contains($subNameLower, $inputLower) || str_contains($inputLower, $subNameLower)) {
                return $subCat;
            }
        }

        // 3. Similarity / Fuzzy matching
        $bestMatch   = null;
        $highestPerc = 0;

        foreach ($subCategories as $subCat) {
            $subNameLower = mb_strtolower($subCat->name);
            similar_text($inputLower, $subNameLower, $percent);

            if ($percent > $highestPerc) {
                $highestPerc = $percent;
                $bestMatch   = $subCat;
            }
        }

        if ($highestPerc >= 45.0) {
            return $bestMatch;
        }

        return null;
    }
}
