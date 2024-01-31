<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ env('APP_NAME') }} | Swagger</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|Source+Code+Pro:300,600|Titillium+Web:400,600,700" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('documentation/css/swagger-ui.css') }}" >
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/gif" sizes="16x16">
    <style>
        html
        {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after
        {
            box-sizing: inherit;
        }

        body
        {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>

<body>

<div id="swagger-ui"></div>

<script src="{{ asset('documentation/js/swagger-ui-bundle.js') }}"> </script>
<script src="{{ asset('documentation/js/swagger-ui-standalone-preset.js') }}"> </script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js'> </script>
<script>
    window.onload = function() {

        var jsonUrl = "{{ route('json-url') }}";

        const ui = SwaggerUIBundle({
            url: jsonUrl,
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            onComplete: function() {
                // Default API key
            }
        });

        // ui.authActions.authorize({JWT: {name: "JWT", schema: {type: "apiKey", in: "header", name: "Authorization", description: ""}, value: "Bearer <JWT>"}});

        window.ui = ui;
    };

</script>
</body>
</html>
