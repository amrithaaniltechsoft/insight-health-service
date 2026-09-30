<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only recommendations that drive the service calculator on a service
 * listing page — for pregnancy scans, which scan suits which gestation week.
 *
 * Lives here for the same reason as PeopleController and BookingController: the
 * database is on this host, so a Vercel function cannot read it.
 */
class AssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $category = trim((string) $request->query('category', ''));

        if ($category === '') {
            return response()->json(['error' => 'Missing category parameter'], 400);
        }

        $rows = DB::table('assessment_recommendations')
            ->where('category_slug', $category)
            ->orderBy('sort_order')
            ->get([
                'id',
                'category_slug',
                'widget_type',
                'option_value',
                'min_value',
                'max_value',
                'title',
                'slug',
                'description',
                'sort_order',
            ]);

        $recommendations = $rows->map(fn ($row) => (array) $row)->all();

        // The widget either wants every row for the category, or the subset for
        // one widget state — a gestation week, or a chosen option. The value and
        // option params are mutually exclusive, so value is checked first.
        $value = $request->query('value');
        if ($value !== null && is_numeric($value)) {
            $value = (float) $value;

            $recommendations = array_values(array_filter(
                $recommendations,
                fn (array $row) => ($row['widget_type'] ?? null) === 'range'
                    && ($row['min_value'] ?? 0) <= $value
                    && ($row['max_value'] ?? PHP_INT_MAX) >= $value
            ));

            return response()->json(['recommendations' => $recommendations]);
        }

        $option = $request->query('option');
        if ($option !== null) {
            $recommendations = array_values(array_filter(
                $recommendations,
                fn (array $row) => $row['option_value'] === $option
            ));
        }

        return response()->json(['recommendations' => $recommendations]);
    }
}
