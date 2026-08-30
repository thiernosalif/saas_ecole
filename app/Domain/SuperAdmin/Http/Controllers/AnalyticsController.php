<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Services\AnalyticsService;
use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function index()
    {
        return response()->json($this->analytics->resume());
    }
}
