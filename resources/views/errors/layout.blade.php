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
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .error-container {
            width: 100%;
            max-width: 900px;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-header {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .error-code {
            font-size: 3rem;
            font-weight: 700;
            color: #e53e3e;
            margin-bottom: 0.5rem;
            font-family: monospace;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .error-message {
            font-size: 1rem;
            color: #718096;
            margin-bottom: 0;
            line-height: 1.6;
        }

        .error-details {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .error-details-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #718096;
        }

        .error-details-item {
            margin-bottom: 1.5rem;
        }

        .error-details-item:last-child {
            margin-bottom: 0;
        }

        .error-details-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .error-details-content {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.85rem;
            color: #2d3748;
            background: #f7fafc;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            border-left: 3px solid #cbd5e0;
            word-break: break-word;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 200px;
            overflow-y: auto;
            line-height: 1.5;
        }

        .stack-trace {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 4px;
            border-left: 3px solid #cbd5e0;
        }

        .stack-trace-item {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.8rem;
            color: #2d3748;
            padding: 0.5rem 0;
            line-height: 1.4;
            border-bottom: 1px solid #e2e8f0;
        }

        .stack-trace-item:last-child {
            border-bottom: none;
        }

        .stack-trace-file {
            color: #667eea;
            font-weight: 500;
        }

        .stack-trace-line {
            color: #a0aec0;
            margin-left: 1rem;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        @media (max-width: 768px) {
            .error-container {
                max-width: 100%;
            }

            .error-header {
                padding: 1.5rem;
            }

            .error-code {
                font-size: 2.5rem;
            }

            .error-title {
                font-size: 1.25rem;
            }

            .error-message {
                font-size: 0.95rem;
            }

            .btn {
                padding: 0.6rem 1.25rem;
                font-size: 0.95rem;
            }

            .error-details-content {
                font-size: 0.8rem;
            }

            .stack-trace-item {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-code">{{ $code ?? 500 }}</div>
            <h1 class="error-title">
                {{ $title ?? match($code ?? 500) {
                    404 => 'Page Not Found',
                    403 => 'Access Denied',
                    default => 'Server Error'
                } }}
            </h1>
            <p class="error-message">
                {{ $message ?? match($code ?? 500) {
                    404 => 'The page you\'re looking for doesn\'t exist.',
                    403 => 'You don\'t have permission to access this resource.',
                    default => 'An unexpected error occurred. Please try again later.'
                } }}
            </p>
        </div>

        @if(config('app.debug') && isset($exception))
            <div class="error-details">
                <div class="error-details-title">Debug Information</div>

                <div class="error-details-item">
                    <div class="error-details-label">Exception:</div>
                    <div class="error-details-content">{{ get_class($exception) }}</div>
                </div>

                <div class="error-details-item">
                    <div class="error-details-label">Message:</div>
                    <div class="error-details-content">{{ $exception->getMessage() }}</div>
                </div>

                <div class="error-details-item">
                    <div class="error-details-label">File:</div>
                    <div class="error-details-content">{{ $exception->getFile() }}:{{ $exception->getLine() }}</div>
                </div>

                @if($exception->getTrace())
                    <div class="error-details-item">
                        <div class="error-details-label">Stack Trace (up to 10 calls):</div>
                        <div class="stack-trace">
                            @foreach(array_slice($exception->getTrace(), 0, 10) as $trace)
                                <div class="stack-trace-item">
                                    @if(isset($trace['file']))
                                        <span class="stack-trace-file">{{ basename($trace['file']) }}</span>
                                        <span class="stack-trace-line">{{ $trace['line'] ?? 'unknown' }}</span>
                                    @endif
                                    @if(isset($trace['class']))
                                        <span class="stack-trace-file">{{ $trace['class'] }}</span>{{ $trace['type'] ?? '' }}<span class="stack-trace-file">{{ $trace['function'] ?? '' }}</span>
                                    @elseif(isset($trace['function']))
                                        <span class="stack-trace-file">{{ $trace['function'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="error-actions">
            <a href="{{ url(config('ave.route_prefix', 'admin')) }}" class="btn btn-primary">
                Back to Dashboard
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
