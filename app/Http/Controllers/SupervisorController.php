<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupervisorProgramRequest;
use App\Services\SupervisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function __construct(protected SupervisorService $supervisor) {}

    public function index(Request $request)
    {
        $containers = $this->supervisor->getContainers();
        $active = $request->query('container', array_key_first($containers));

        if (!$this->supervisor->isValidContainer($active)) {
            $active = array_key_first($containers);
        }

        return view('supervisor.index', [
            'containers' => $containers,
            'active' => $active,
            'processes' => $this->supervisor->getProcesses($active),
            'configs' => $this->supervisor->getConfigs($active),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $container = $request->query('container', 'all');

        if ($container === 'all') {
            return response()->json(['processes' => $this->supervisor->getAllProcesses()]);
        }

        if (!$this->supervisor->isValidContainer($container)) {
            return response()->json(['error' => 'Invalid container.'], 422);
        }

        return response()->json([
            'processes' => $this->supervisor->getProcesses($container),
            'configs' => $this->supervisor->getConfigs($container),
        ]);
    }

    public function startProcess(string $container, string $process): JsonResponse
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return response()->json(['success' => false, 'message' => 'Invalid container.'], 422);
        }
        if (!$this->supervisor->isValidProcessName($process)) {
            return response()->json(['success' => false, 'message' => 'Invalid process name.'], 422);
        }

        $output = $this->supervisor->startProcess($container, $process);
        $success = str_contains($output, 'started') || str_contains($output, 'ERROR (already started)');

        return response()->json(['success' => $success, 'message' => $output ?: 'No output']);
    }

    public function stopProcess(string $container, string $process): JsonResponse
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return response()->json(['success' => false, 'message' => 'Invalid container.'], 422);
        }
        if (!$this->supervisor->isValidProcessName($process)) {
            return response()->json(['success' => false, 'message' => 'Invalid process name.'], 422);
        }

        $output = $this->supervisor->stopProcess($container, $process);
        $success = str_contains($output, 'stopped') || str_contains($output, 'ERROR (not running)');

        return response()->json(['success' => $success, 'message' => $output ?: 'No output']);
    }

    public function restartProcess(string $container, string $process): JsonResponse
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return response()->json(['success' => false, 'message' => 'Invalid container.'], 422);
        }
        if (!$this->supervisor->isValidProcessName($process)) {
            return response()->json(['success' => false, 'message' => 'Invalid process name.'], 422);
        }

        $output = $this->supervisor->restartProcess($container, $process);
        $success = str_contains($output, 'started') || str_contains($output, 'ERROR (already started)');

        return response()->json(['success' => $success, 'message' => $output ?: 'No output']);
    }

    public function restartAll(string $container): JsonResponse
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return response()->json(['success' => false, 'message' => 'Invalid container.'], 422);
        }

        $output = $this->supervisor->restartAll($container);

        return response()->json(['success' => true, 'message' => $output ?: 'All processes restarted.']);
    }

    public function createProgram()
    {
        return view('supervisor.programs.create', [
            'containers' => $this->supervisor->getContainers(),
        ]);
    }

    public function storeProgram(SupervisorProgramRequest $request)
    {
        $data = $request->validated();
        $container = $data['container'];
        $programName = $data['program_name'];

        $settings = $this->extractSettings($data);
        $output = $this->supervisor->createConfig($container, $programName, $settings);

        return redirect()
            ->route('supervisor.index', ['container' => $container])
            ->with('success', "Program '{$programName}' created. {$output}");
    }

    public function editProgram(string $container, string $file)
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return redirect()->route('supervisor.index')->with('error', 'Invalid container.');
        }
        if (!$this->supervisor->isValidFilename($file)) {
            return redirect()->route('supervisor.index')->with('error', 'Invalid filename.');
        }

        $configs = $this->supervisor->getConfigs($container);
        $config = collect($configs)->firstWhere('file', $file);

        if (!$config) {
            return redirect()->route('supervisor.index', ['container' => $container])->with('error', 'Config not found.');
        }

        $settings = $this->supervisor->parseConfigContent($config['content']);

        return view('supervisor.programs.edit', [
            'containers' => $this->supervisor->getContainers(),
            'container' => $container,
            'file' => $file,
            'settings' => $settings,
        ]);
    }

    public function updateProgram(SupervisorProgramRequest $request, string $container, string $file)
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return redirect()->route('supervisor.index')->with('error', 'Invalid container.');
        }
        if (!$this->supervisor->isValidFilename($file)) {
            return redirect()->route('supervisor.index')->with('error', 'Invalid filename.');
        }

        $data = $request->validated();
        $programName = $data['program_name'];
        $settings = $this->extractSettings($data);

        $output = $this->supervisor->updateConfig($container, $file, $programName, $settings);

        return redirect()
            ->route('supervisor.index', ['container' => $container])
            ->with('success', "Program '{$programName}' updated. {$output}");
    }

    public function deleteProgram(string $container, string $file)
    {
        if (!$this->supervisor->isValidContainer($container)) {
            return redirect()->route('supervisor.index')->with('error', 'Invalid container.');
        }
        if (!$this->supervisor->isValidFilename($file)) {
            return redirect()->route('supervisor.index')->with('error', 'Invalid filename.');
        }

        $output = $this->supervisor->deleteConfig($container, $file);

        return redirect()
            ->route('supervisor.index', ['container' => $container])
            ->with('success', "Program deleted. {$output}");
    }

    protected function extractSettings(array $data): array
    {
        $settings = [];

        foreach (['command', 'directory', 'user', 'stdout_logfile', 'stdout_logfile_maxbytes'] as $key) {
            if (!empty($data[$key])) {
                $settings[$key] = $data[$key];
            }
        }

        foreach (['numprocs', 'stdout_logfile_backups', 'stopwaitsecs'] as $key) {
            if (isset($data[$key])) {
                $settings[$key] = (int) $data[$key];
            }
        }

        foreach (['autostart', 'autorestart', 'redirect_stderr', 'stopasgroup', 'killasgroup'] as $key) {
            if (isset($data[$key])) {
                $settings[$key] = (bool) $data[$key];
            }
        }

        return $settings;
    }
}
