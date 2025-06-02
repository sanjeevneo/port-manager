<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #0f1419; color: #e6edf3; min-height: 100vh; display: flex; flex-direction: column; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; flex: 1; }
        .header { text-align: center; margin-bottom: 3rem; padding: 2rem; background: linear-gradient(135deg, #1e3a5f 0%, #0d1117 100%); border-radius: 1rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; background: linear-gradient(135deg, #58a6ff 0%, #79c0ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header p { color: #8b949e; font-size: 1.1rem; }
        .controls { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; font-size: 1rem; cursor: pointer; transition: all 0.2s; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #238636; color: white; }
        .btn-primary:hover { background: #2ea043; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(35, 134, 54, 0.3); }
        .btn-secondary { background: #21262d; color: #c9d1d9; border: 1px solid #30363d; }
        .btn-secondary:hover { background: #30363d; border-color: #8b949e; }
        .port-display { background: #0d1117; border: 1px solid #30363d; border-radius: 0.75rem; padding: 2rem; margin-bottom: 2rem; text-align: center; display: none; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .port-number { font-size: 4rem; font-weight: bold; font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace; background: linear-gradient(135deg, #58a6ff 0%, #79c0ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 1rem 0; }
        .port-info { color: #8b949e; margin-bottom: 1rem; }
        .copy-section { display: flex; gap: 1rem; justify-content: center; align-items: center; margin-top: 1.5rem; }
        .copy-command { background: #161b22; border: 1px solid #30363d; border-radius: 0.5rem; padding: 0.75rem 1.5rem; font-family: 'SF Mono', Monaco, monospace; font-size: 0.9rem; color: #f0f6fc; }
        .output-container { background: #0d1117; border: 1px solid #30363d; border-radius: 0.75rem; padding: 1.5rem; margin-top: 2rem; margin-bottom: 2rem; display: none; max-height: 600px; overflow-y: auto; }
        .output-container pre { font-family: 'SF Mono', Monaco, monospace; font-size: 0.875rem; line-height: 1.5; color: #c9d1d9; white-space: pre-wrap; word-wrap: break-word; }
        .loading { display: none; text-align: center; padding: 2rem; }
        .spinner { border: 3px solid #30363d; border-top: 3px solid #58a6ff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error { background: #1f1418; border: 1px solid #f85149; color: #f85149; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; display: none; }
        .success-toast { position: fixed; top: 2rem; right: 2rem; background: #238636; color: white; padding: 1rem 1.5rem; border-radius: 0.5rem; display: none; animation: slideInRight 0.3s ease-out; z-index: 1000; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        .verification-status { display: flex; flex-direction: column; gap: 0.25rem; text-align: left; font-size: 0.95rem; color: #c9d1d9; padding: 0.5rem 1rem; background-color: rgba(36, 41, 46, 0.3); border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .verification-status div { display: flex; justify-content: space-between; align-items: center; }
        .verification-status span.checkmark { font-size: 1.1rem; font-weight: bold; }
        #port-mindmap { flex: 1; height: 60vh; margin: 20px 0; background: #0d1117; border: 1px solid #30363d; border-radius: 0.75rem; }
        .mindmap-controls { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; align-items: center; }
        .mindmap-controls input[type="text"] { flex: 1; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #30363d; background: #161b22; color: #e6edf3; }
        .mindmap-controls button { padding: 0.5rem 1rem; background: #238636; color: white; border: none; border-radius: 0.5rem; cursor: pointer; }
        @media (max-width: 640px) { #port-mindmap { height: 50vh; } .mindmap-controls { flex-direction: column; align-items: stretch; } }
        .footer { text-align: center; padding: 2rem; color: #8b949e; font-size: 0.875rem; }
        @media (max-width: 640px) { .container { padding: 1rem; } .header h1 { font-size: 2rem; } .controls { flex-direction: column; } .btn { width: 100%; justify-content: center; } .port-number { font-size: 3rem; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Port Manager</h1>
            <p>Generate safe random ports for Docker containers</p>
        </div>
        <div class="controls">
            <button class="btn btn-primary" onclick="generatePort()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="9" cy="9" r="1"></circle>
                    <circle cx="15" cy="9" r="1"></circle>
                    <circle cx="9" cy="15" r="1"></circle>
                    <circle cx="15" cy="15" r="1"></circle>
                </svg>
                Generate Random Port
            </button>
            <button class="btn btn-secondary" onclick="showFullInfo()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <circle cx="12" cy="8" r="1"></circle>
                </svg>
                Show Full Info
            </button>
            <button class="btn btn-secondary" onclick="toggleMindMap()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20v-6m0-6V4m0 0h4m-4 0H8"></path>
                </svg>
                Show All Ports Map
            </button>
            <button class="btn btn-secondary" onclick="clearDisplay()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Clear
            </button>
        </div>
        <div class="loading">
            <div class="spinner"></div>
            <p>Scanning for available ports...</p>
        </div>
        <div class="port-display" id="portDisplay">
            <div class="port-info"></div>
            <div class="port-number" id="portNumber">-</div>
            <div class="port-info">Safe range: <span id="safeRange">10000-59151</span></div>
            <div class="copy-section">
                <div class="copy-command" id="dockerCommand">docker run -p PORT:80 nginx</div>
                <button class="btn btn-secondary" onclick="copyCommand()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    Copy
                </button>
            </div>
        </div>
        <div class="error" id="errorDisplay"></div>
        <div class="output-container" id="outputContainer">
            <pre id="outputContent"></pre>
        </div>

        <div id="mindmapSection" style="display: none;">
            <div class="mindmap-controls">
                <input type="text" id="searchInput" placeholder="Search node...">
                <button onclick="searchNode()">Find</button>
            </div>
            <div id="port-mindmap" style="height: 400px; margin: 20px 0;"></div>
        </div>

    </div>


    <script src="js/vis-network.min.js"></script>
    <script>
    let network;
    let nodes = new vis.DataSet();
    let edges = new vis.DataSet();
    let mindMapLoaded = false;

    function toggleMindMap() {
        const mindmapSection = document.getElementById('mindmapSection');

        if (mindmapSection.style.display === 'none') {
            if (!mindMapLoaded) {
                fetch('api.php?mode=docker_ports')
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to fetch ports data');
                        return response.json();
                    })
                    .then(data => {
                        if (!Array.isArray(data) || data.length === 0) {
                            alert('No ports data found.');
                            return;
                        }
                        buildMindMap(data);
                        mindMapLoaded = true;
                        mindmapSection.style.display = 'block';
                        network.redraw();
                        network.fit();
                    })
                    .catch(error => {
                        console.error('Error loading ports data:', error);
                        alert('Failed to load ports data.');
                    });
            } else {
                mindmapSection.style.display = 'block';
                network.redraw();
                network.fit();
            }
        } else {
            mindmapSection.style.display = 'none';
        }
    }


    function buildMindMap(data) {
        nodes.clear();
        edges.clear();
        data.forEach(container => {
            nodes.add({ id: container.container, label: container.container });
            container.ports.forEach(port => {
                const portId = `${container.container}-${port.host_port}`;
                nodes.add({ id: portId, label: port.host_port });
                edges.add({ from: container.container, to: portId });
            });
        });
        const containerElement = document.getElementById('port-mindmap');
        const networkData = { nodes: nodes, edges: edges };
        const options = {
            nodes: { shape: 'box', font: { size: 16 } },
            edges: { arrows: 'to' }
        };
        network = new vis.Network(containerElement, networkData, options);
    }

    let searchMatches = [];
    let currentMatchIndex = 0;

    document.getElementById('searchInput').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            const searchTerm = this.value.toLowerCase();
            const allNodes = nodes.get();

            if (searchMatches.length === 0 || !searchMatches[0].label.toLowerCase().includes(searchTerm)) {
                searchMatches = allNodes.filter(node => node.label.toLowerCase().includes(searchTerm));
                currentMatchIndex = 0;
            } else {
                currentMatchIndex = (currentMatchIndex + 1) % searchMatches.length;
            }

            if (searchMatches.length > 0) {
                const node = searchMatches[currentMatchIndex];
                network.focus(node.id, {
                    scale: 1.5,
                    animation: { duration: 1000, easingFunction: "easeInOutQuad" }
                });
                network.selectNodes([node.id]);
            } else {
                alert('No matches found.');
            }
        }
    });


    </script>

    <div class="footer">
        Port Manager • Port Range: <span id="portRange">10000-59151</span> • Synology NAS
    </div>
    <div class="success-toast" id="successToast">
        Command copied to clipboard!
    </div>
    <script>
        let currentPort = null;

        async function executeCommand(mode = 'port', port = null) {
            const params = new URLSearchParams({ mode });
            if (port !== null) {
                params.append('port', port);
            }
            const response = await fetch(`api.php?${params}`);
            const data = await response.json();
            if (!response.ok || data.error) {
                throw new Error(data.error || 'Request failed');
            }
            return data;
        }

        async function generatePort() {
            const loadingElement = document.querySelector('.loading');
            const portDisplayElement = document.getElementById('portDisplay');
            const errorElement = document.getElementById('errorDisplay');
            const outputElement = document.getElementById('outputContainer');

            loadingElement.style.display = 'block';
            portDisplayElement.style.display = 'none';
            errorElement.style.display = 'none';
            outputElement.style.display = 'none';

            try {
                const result = await executeCommand('port');
                currentPort = result.port;
                document.getElementById('safeRange').textContent = `${result.min_port}-${result.max_port}`;
                document.getElementById('portRange').textContent = `${result.min_port}-${result.max_port}`;
                document.getElementById('portNumber').textContent = currentPort;
                document.getElementById('dockerCommand').textContent = `docker run -p ${currentPort}:80 nginx`;

                const verification = result.verification;
                document.querySelector('.port-display .port-info').innerHTML = `
                    <div class="verification-status">
                        <div><span>Socket Test</span> <span class="checkmark">${verification.socket_test ? '✅' : '❌'}</span></div>
                        <div><span>Docker Test</span> <span class="checkmark">${verification.not_in_docker ? '✅' : '❌'}</span></div>
                        <div><span>System Test</span> <span class="checkmark">${verification.not_in_system ? '✅' : '❌'}</span></div>
                        <div><span>Range Test</span> <span class="checkmark">${verification.safe_range ? '✅' : '❌'}</span></div>
                    </div>
                `;

                loadingElement.style.display = 'none';
                portDisplayElement.style.display = 'block';
            } catch (error) {
                loadingElement.style.display = 'none';
                errorElement.style.display = 'block';
                errorElement.textContent = `Error: ${error.message}`;
            }
        }

        async function showFullInfo() {
            const loadingElement = document.querySelector('.loading');
            const outputElement = document.getElementById('outputContainer');
            const outputContent = document.getElementById('outputContent');
            const errorElement = document.getElementById('errorDisplay');

            loadingElement.style.display = 'block';
            outputElement.style.display = 'none';
            errorElement.style.display = 'none';

            try {
                const result = await executeCommand('full', currentPort);
                currentPort = result.port;
                document.getElementById('portNumber').textContent = currentPort;
                document.getElementById('dockerCommand').textContent = `docker run -p ${currentPort}:80 nginx`;
                outputContent.textContent = JSON.stringify(result, null, 2);
                loadingElement.style.display = 'none';
                outputElement.style.display = 'block';
            } catch (error) {
                loadingElement.style.display = 'none';
                errorElement.style.display = 'block';
                errorElement.textContent = `Error: ${error.message}`;
            }
        }

        function copyCommand() {
            const command = document.getElementById('dockerCommand').textContent;
            navigator.clipboard.writeText(command).then(() => {
                const toast = document.getElementById('successToast');
                toast.style.display = 'block';
                setTimeout(() => { toast.style.display = 'none'; }, 3000);
            });
        }

        function clearDisplay() {
            document.getElementById('portDisplay').style.display = 'none';
            document.getElementById('outputContainer').style.display = 'none';
            document.getElementById('errorDisplay').style.display = 'none';
            currentPort = null;
        }

        window.addEventListener('DOMContentLoaded', () => { generatePort(); });
    </script>
</body>
</html>
