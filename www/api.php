<?php
declare(strict_types=1);

$config = parse_ini_file('conf.ini');
if ($config === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load config.ini']);
    exit;
}

define('MIN_SAFE_PORT', (int)$config['MIN_SAFE_PORT']);
define('MAX_SAFE_PORT', (int)$config['MAX_SAFE_PORT']);
define('MAX_PORT_ATTEMPTS', (int)$config['MAX_PORT_ATTEMPTS']);
define('SOCKET_TIMEOUT', (float)$config['SOCKET_TIMEOUT']);
define('DOCKER_PATH', $config['DOCKER_PATH']);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

function executeCommand(string $command): array {
    $output = [];
    $returnCode = 0;
    exec("$command 2>/dev/null", $output, $returnCode);
    return $returnCode === 0 ? array_filter($output, 'strlen') : [];
}

function getDockerContainers(): array {
    $runningCommand = "sudo " . DOCKER_PATH . " ps --format '{{.ID}}|{{.Names}}|{{.Ports}}|{{.Status}}'";
    $stoppedCommand = "sudo " . DOCKER_PATH . " ps -a --filter 'status=exited' --format '{{.ID}}|{{.Names}}|{{.Ports}}|{{.Status}}'";
    return [
        'running' => parseContainerOutput(executeCommand($runningCommand)),
        'stopped' => parseContainerOutput(executeCommand($stoppedCommand))
    ];
}

function parseContainerOutput(array $lines): array {
    $containers = [];
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 4) {
            $containers[] = [
                'id' => substr($parts[0], 0, 12),
                'name' => $parts[1],
                'ports' => parsePortMappings($parts[2]),
                'uptime' => $parts[3]
            ];
        }
    }
    return $containers;
}

function parsePortMappings(string $portString): array {
    if (empty($portString)) {
        return [];
    }
    $ports = [];
    foreach (explode(', ', $portString) as $entry) {
        if (preg_match('/(?:0\.0\.0\.0:)?(\d+)->(\d+)\/(\w+)/', $entry, $matches)) {
            $ports[] = [
                'host' => (int)$matches[1],
                'container' => (int)$matches[2],
                'protocol' => $matches[3]
            ];
        }
    }
    return $ports;
}

function getAllDockerPorts(array $containers): array {
    $ports = [];
    foreach (['running', 'stopped'] as $status) {
        foreach ($containers[$status] as $container) {
            foreach ($container['ports'] as $port) {
                $ports[] = $port['host'];
            }
        }
    }
    return array_unique($ports);
}

function getSystemPorts(): array {
    $ports = executeCommand("ss -tlnp | awk 'NR>1 {print \$4}' | grep -o '[0-9]*\$' | sort -n | uniq");
    return empty($ports) ?
        array_map('intval', executeCommand("netstat -tln | awk 'NR>2 {print \$4}' | grep -o '[0-9]*\$' | sort -n | uniq")) :
        array_map('intval', $ports);
}

function isPortAvailable(int $port): bool {
    $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, SOCKET_TIMEOUT);
    if ($socket) {
        fclose($socket);
        return false;
    }
    return true;
}

function generateAvailablePort(array $excludedPorts): ?int {
    for ($attempt = 0; $attempt < MAX_PORT_ATTEMPTS; $attempt++) {
        $port = random_int(MIN_SAFE_PORT, MAX_SAFE_PORT);
        if (!in_array($port, $excludedPorts, true) && isPortAvailable($port)) {
            return $port;
        }
    }
    return null;
}

function verifyDockerAccess(): bool {
    return !empty(executeCommand("sudo " . DOCKER_PATH . " version --format '{{.Server.Version}}'"));
}

function generatePortVerification(int $port, array $dockerPorts, array $systemPorts): array {
    return [
        'socket_test' => isPortAvailable($port),
        'not_in_docker' => !in_array($port, $dockerPorts, true),
        'not_in_system' => !in_array($port, $systemPorts, true),
        'safe_range' => $port >= MIN_SAFE_PORT && $port <= MAX_SAFE_PORT
    ];
}

function formatFullOutput(array $containers, array $dockerPorts, array $systemPorts, int $port): array {
    $allPorts = array_unique(array_merge($dockerPorts, $systemPorts));
    return [
        'success' => true,
        'port' => $port,
        'summary' => [
            'containers' => [
                'total' => count($containers['running']) + count($containers['stopped']),
                'running' => count($containers['running']),
                'stopped' => count($containers['stopped'])
            ],
            'ports' => [
                'docker' => count($dockerPorts),
                'system' => count($systemPorts),
                'total_used' => count($allPorts),
                'range' => [
                    'min' => MIN_SAFE_PORT,
                    'max' => MAX_SAFE_PORT,
                    'capacity' => MAX_SAFE_PORT - MIN_SAFE_PORT + 1
                ]
            ]
        ],
        'verification' => generatePortVerification($port, $dockerPorts, $systemPorts),
        'usage_examples' => [
            'docker_run' => "docker run -d -p $port:80 nginx",
            'docker_compose' => "ports:\n  - \"$port:80\""
        ]
    ];
}

function handleRequest(): void {
    $mode = $_GET['mode'] ?? 'port';
    if (!in_array($mode, ['port', 'full', 'verify'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid mode']);
        return;
    }

    if (!verifyDockerAccess()) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'Docker not accessible']);
        return;
    }

    $containers = getDockerContainers();
    $dockerPorts = getAllDockerPorts($containers);
    $systemPorts = getSystemPorts();
    $allUsedPorts = array_unique(array_merge($dockerPorts, $systemPorts));

    if ($mode === 'verify' && isset($_GET['port'])) {
        $port = filter_var($_GET['port'], FILTER_VALIDATE_INT);
        if ($port === false || $port < 1 || $port > 65535) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid port number']);
            return;
        }
        echo json_encode([
            'success' => true,
            'port' => $port,
            'available' => !in_array($port, $allUsedPorts, true) && isPortAvailable($port),
            'verification' => generatePortVerification($port, $dockerPorts, $systemPorts)
        ]);
        return;
    }

    if ($mode === 'full' && isset($_GET['port']) && is_numeric($_GET['port'])) {
        $port = (int)$_GET['port'];
        if ($port < 1 || $port > 65535) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid port number']);
            return;
        }
        echo json_encode(formatFullOutput($containers, $dockerPorts, $systemPorts, $port));
        return;
    }

    $port = generateAvailablePort($allUsedPorts);
    if ($port === null) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'No available ports found']);
        return;
    }

    if ($mode === 'port') {
        echo json_encode([
            'success' => true,
            'port' => $port,
            'verification' => generatePortVerification($port, $dockerPorts, $systemPorts)
        ]);
    } else {
        echo json_encode(formatFullOutput($containers, $dockerPorts, $systemPorts, $port));
    }
}

handleRequest();
