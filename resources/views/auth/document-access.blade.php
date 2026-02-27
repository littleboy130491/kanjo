<!doctype html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
<div class="w-full max-w-md rounded-xl bg-white p-6 shadow">
    <h1 class="text-xl font-semibold text-slate-900">Document Access</h1>
    <p class="mt-2 text-sm text-slate-600">{{ $document->document_number }}</p>

    @if ($errors->has('credentials'))
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first('credentials') }}
        </div>
    @endif

    <form method="POST" action="{{ $authRoute }}" class="mt-5 space-y-4">
        @csrf
        <input type="hidden" name="lang" value="{{ $lang }}">

        <div>
            <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
            <input id="username" name="username" type="text" required value="{{ old('username') }}" class="w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input id="password" name="password" type="password" required class="w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <button type="submit" class="w-full rounded-md bg-slate-900 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Open {{ ucfirst($documentType) }}
        </button>
    </form>
</div>
</body>
</html>
