<!DOCTYPE html>
<html lang="{{ config('app.locale', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code ?? 500 }} - {{ config('app.name', 'Ave') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/ave/css/app.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .error-container {
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-logo {
            margin-bottom: 2rem;
        }

        .error-logo img {
            height: 60px;
            width: auto;
        }

        .error-code {
            font-size: 6rem;
            font-weight: 900;
            letter-spacing: -2px;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-code.code-404 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .error-code.code-403 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .error-code.code-500 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .error-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }

        .error-message {
            font-size: 1.125rem;
            color: #718096;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .error-details {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #e53e3e;
        }

        .error-details-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .error-details-content {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.875rem;
            color: #718096;
            word-break: break-word;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            background: #f7fafc;
            padding: 0.75rem;
            border-radius: 4px;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.75rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f7fafc;
            transform: translateY(-2px);
        }

        @media (max-width: 640px) {
            .error-code {
                font-size: 4rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-message {
                font-size: 1rem;
            }

            .btn {
                padding: 0.625rem 1.5rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-logo">
            <img src="{{ asset('vendor/ave/assets/images/logo-icon-light.png') }}" alt="{{ config('app.name', 'Ave') }}">
        </div>

        <div class="error-code code-{{ $code ?? 500 }}">
            {{ $code ?? 500 }}
        </div>

        <h1 class="error-title">
            {{ $title ?? match($code ?? 500) {
                404 => 'Page Not Found',
                403 => 'Access Denied',
                default => 'Something Went Wrong'
            } }}
        </h1>

        <p class="error-message">
            {{ $message ?? match($code ?? 500) {
                404 => 'The page you\'re looking for doesn\'t exist.',
                403 => 'You don\'t have permission to access this resource.',
                default => 'An unexpected error occurred. Please try again later.'
            } }}
        </p>

        @if(config('app.debug') && isset($exception))
            <div class="error-details">
                <div class="error-details-title">🔍 Debug Information:</div>
                <div class="error-details-content">{{ $exception->getMessage() }}</div>
            </div>
        @endif

        <div class="error-actions">
            <a href="{{ url(config('ave.route_prefix', 'admin')) }}" class="btn btn-primary">
                ← Back to Dashboard
            </a>
            @if($code !== 404)
                <a href="javascript:history.back()" class="btn btn-secondary">
                    Go Back
                </a>
            @endif
        </div>
    </div>
</body>
</html>
