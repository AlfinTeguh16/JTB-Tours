<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>JTB Tours</title>
  @vite('resources/css/app.css')
</head>
<body>
  <div class="max-w-md mx-auto mt-12">
    <div class="bg-white p-6 rounded shadow">
      <h2 class="text-2xl font-semibold mb-4">Login JTB Tours</h2>
  
      @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-4">{{ session('success') }}</div>
      @endif
  
      <form method="POST" action="{{ route('login.post') }}">
        @csrf
  
        <label class="block mb-2">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full border p-2 rounded mb-3">
        @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
  
        <label class="block mb-2">Password</label>
        <input type="password" name="password" required class="w-full border p-2 rounded mb-3">
        @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
  
  
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Login</button>
      </form>
  
    </div>
  </div>
  
</body>
</html>


