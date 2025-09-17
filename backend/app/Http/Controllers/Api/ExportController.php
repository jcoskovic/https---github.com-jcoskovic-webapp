<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPdfRequest;
use App\Models\Abbreviation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    /**
     * Export abbreviations to PDF
     */
    public function exportPdf(ExportPdfRequest $request): Response
    {
        // Build query
        $query = Abbreviation::with(['user', 'votes', 'comments.user'])
            ->where('status', 'approved'); // Only export approved abbreviations

        // Filter by specific IDs if provided
        if ($request->has('abbreviation_ids') && ! empty($request->abbreviation_ids)) {
            $query->whereIn('id', $request->abbreviation_ids);
        } else {
            // Apply search filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('abbreviation', 'like', "%$search%")
                        ->orWhere('meaning', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%");
                });
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
        }

        $abbreviations = $query->orderBy('abbreviation')->get();

        if ($abbreviations->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No abbreviations found for export',
            ], 404);
        }

        try {
            $format = $request->get('format', 'simple');
            $viewName = $format === 'detailed' ? 'pdf.abbreviations-detailed' : 'pdf.abbreviations-simple';

            // Generate PDF
            $pdf = Pdf::loadView($viewName, [
                'abbreviations' => $abbreviations,
                'exportDate' => now()->format('d.m.Y H:i'),
                'totalCount' => $abbreviations->count(),
                'filters' => [
                    'search' => $request->search,
                    'category' => $request->category,
                ],
            ]);

            $filename = 'abbreviations_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test PDF generation step by step
     */
    public function testPdf(): Response
    {
        try {
            // Step 1: Test data
            $abbreviations = collect([
                (object) [
                    'id' => 1,
                    'abbreviation' => 'TEST',
                    'meaning' => 'Test Meaning',
                    'description' => 'Test Description',
                    'category' => 'Test Category',
                    'user' => (object) ['name' => 'Test User'],
                    'votes' => collect([]),
                    'comments' => collect([]),
                    'created_at' => now()
                ]
            ]);

            // Step 2: Test template rendering
            $html = view('pdf.abbreviations-simple', [
                'abbreviations' => $abbreviations,
                'exportDate' => now()->format('d.m.Y H:i'),
                'totalCount' => 1,
                'filters' => ['search' => null, 'category' => null],
            ])->render();

            // Step 3: Test PDF generation
            $pdf = Pdf::loadHTML($html);
            
            return response()->json([
                'status' => 'success',
                'message' => 'PDF test successful',
                'html_length' => strlen($html),
                'html_preview' => substr($html, 0, 200) . '...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'PDF test failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
