<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body { margin: 0; padding: 0; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const spec = @json($spec);

            // Replace server variables with current host
            const protocol = window.location.protocol.replace(':', '');
            spec.servers.forEach(server => {
                server.variables.protocol.default = protocol;
                server.variables.host.default = window.location.host;
            });

            SwaggerUIBundle({
                spec: spec,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.StandalonePreset,
                ],
                layout: 'StandaloneLayout',
                persistAuthorization: true,
                displayRequestDuration: true,
                filter: true,
            });
        });
    </script>
</body>
</html>
