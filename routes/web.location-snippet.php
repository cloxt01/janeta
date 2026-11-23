<?php

/*
|--------------------------------------------------------------------------
| SNIPPET: tempel ke routes/web.php
|--------------------------------------------------------------------------
| 1. Bagian A masuk di dalam Route::middleware('auth')->group(...)
|    tetapi DI LUAR group 'location.required'.
| 2. Bagian B masuk di dalam group 'location.required' (menggantikan
|    blok "LOCATION ADMIN" yang lama).
| 3. Hapus route & controller LocationSocketController (tidak dipakai lagi).
|--------------------------------------------------------------------------
*/
