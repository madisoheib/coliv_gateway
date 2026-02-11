<?php

namespace App\Http\Controllers;

use App\Services\DockerService;
use Illuminate\Http\JsonResponse;

class DockerController extends Controller
{
    public function __construct(protected DockerService $docker) {}

    public function index()
    {
        return view('docker.index', [
            'containers' => $this->docker->getStats(),
            'allowedContainers' => $this->docker->getAllowedContainers(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'containers' => $this->docker->getStats(),
        ]);
    }

    public function restart(string $container): JsonResponse
    {
        $result = $this->docker->restartContainer($container);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
