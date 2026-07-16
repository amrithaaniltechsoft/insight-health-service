<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReviewApiController extends Controller
{
    public function index()
    {
        $placeId = env('GOOGLE_PLACE_ID', 'ChIJ_xG3veajcEgRk2H7Zf1eWKc');
        $apiKey = env('GOOGLE_PLACES_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'reviews' => [],
                'rating' => 0,
                'total_ratings' => 0,
                'error' => 'Google API key not configured'
            ]);
        }

        $reviews = Cache::remember('google_reviews', 3600, function () use ($placeId, $apiKey) {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'reviews,rating,user_ratings_total',
                'key' => $apiKey,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                return null;
            }

            $result = $data['result'] ?? [];

            $formatted = [];
            foreach (($result['reviews'] ?? []) as $review) {
                $formatted[] = [
                    'name' => $review['author_name'] ?? 'Anonymous',
                    'rating' => (string) ($review['rating'] ?? 5) . '.0',
                    'text' => $review['text'] ?? '',
                    'date' => $this->relativeDate($review['relative_time_description'] ?? $review['time'] ?? null),
                    'profile_photo_url' => $review['profile_photo_url'] ?? null,
                ];
            }

            return [
                'reviews' => $formatted,
                'rating' => $result['rating'] ?? 0,
                'total_ratings' => $result['user_ratings_total'] ?? 0,
            ];
        });

        if ($reviews === null) {
            return response()->json([
                'reviews' => [],
                'rating' => 0,
                'total_ratings' => 0,
                'error' => 'Failed to fetch reviews from Google'
            ]);
        }

        return response()->json($reviews);
    }

    private function relativeDate($time): string
    {
        if (is_string($time)) {
            if (str_contains($time, ' ago') || $time === 'today' || str_contains($time, 'day') || str_contains($time, 'week') || str_contains($time, 'month') || str_contains($time, 'year')) {
                return $time;
            }
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                return $this->relativeTimestamp($timestamp);
            }
            return $time;
        }
        if (is_int($time)) {
            return $this->relativeTimestamp($time);
        }
        return 'recently';
    }

    private function relativeTimestamp(int $time): string
    {
        $diff = time() - $time;
        $days = floor($diff / 86400);
        if ($days < 1) return 'today';
        if ($days === 1) return '1 day ago';
        if ($days < 7) return "$days days ago";
        if ($days < 30) return floor($days / 7) . ' weeks ago';
        if ($days < 365) return floor($days / 30) . ' months ago';
        return floor($days / 365) . ' years ago';
    }

}
