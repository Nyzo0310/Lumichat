<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SelfAssessmentController extends Controller
{
    public function index()
    {
        try {
            // Uncomment these when your DB/table is ready:
            // use App\Models\SelfAssessment;
            // $assessments = SelfAssessment::with('user')->latest()->paginate(10);
            // return view('admin.self-assessments.index', ['assessments' => $assessments, 'demo' => false]);

            throw new QueryException('', [], new \Exception('demo')); // force demo for now
        } catch (\Throwable $e) {
            // -------- DEMO DATA (no DB) --------
            $data = collect($this->demoAssessments());
            $perPage = 10;
            $page = request()->integer('page', 1);
            $slice = $data->forPage($page, $perPage)->values();

            $assessments = new LengthAwarePaginator(
                $slice, $data->count(), $perPage, $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('admin.self-assessments.index', [
                'assessments' => $assessments,
                'demo' => true,
            ]);
        }
    }

    public function show($id)
    {
        try {
            // Uncomment when DB is ready:
            // use App\Models\SelfAssessment;
            // $a = SelfAssessment::with('user')->findOrFail($id);
            // return view('admin.self-assessments.show', ['assessment' => $a, 'demo' => false]);

            throw new QueryException('', [], new \Exception('demo')); // force demo
        } catch (\Throwable $e) {
            // find in demo set by numeric id or by code like ASS-2025-0001
            $all = collect($this->demoAssessments());
            $a = $all->first(function ($row) use ($id) {
                $code = 'ASS-' . date('Y') . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                return (string)$row['id'] === (string)$id || Str::lower($code) === Str::lower($id);
            });

            abort_if(!$a, 404);
            return view('admin.self-assessments.show', [
                'assessment' => (object) $a, // cast to object for easy access
                'demo' => true,
            ]);
        }
    }

    private function demoAssessments(): array
    {
        return [
            [
                'id' => 1,
                'user_name' => 'Juan Dela Cruz',
                'result' => 'Mild Anxiety',
                'created_at' => now()->subDays(2)->toDateTimeString(),
                'answers' => [
                    ['question' => 'Current feeling', 'answer' => 'Anxious about exams'],
                    ['question' => 'Sleep quality', 'answer' => 'Fair'],
                    ['question' => 'Need support?', 'answer' => 'Yes, counseling'],
                ],
                'notes' => null,
            ],
            [
                'id' => 2,
                'user_name' => 'Earl Sepida',
                'result' => 'Normal',
                'created_at' => now()->subDays(3)->toDateTimeString(),
                'answers' => [
                    ['question' => 'Current feeling', 'answer' => 'Curious about LumiCHAT'],
                ],
                'notes' => 'Follow-up optional.',
            ],
            [
                'id' => 3,
                'user_name' => 'Faith Magayon',
                'result' => 'Starting conversation...',
                'created_at' => now()->subDays(4)->toDateTimeString(),
                'answers' => [],
                'notes' => null,
            ],
        ];
    }
}
