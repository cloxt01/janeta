<?php

/*
| SNIPPET: tambahkan ke routes/channels.php (ganti channel 'location-monitoring' yang lama).
|
| Route admin memakai middleware permission:location.view, jadi channel
| juga harus memakai pengecekan permission yang sama (bukan is_admin).
| Sesuaikan `hasPermission()` dengan method di sistem RBAC kamu
| (mis. hasPermissionTo(), can(), dsb).
*/

use Illuminate\Support\Facades\Broadcast;

