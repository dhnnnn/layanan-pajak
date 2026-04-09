<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Forecasting Service URL
    |--------------------------------------------------------------------------
    | URL ke arima-pajak FastAPI service. Di Docker, gunakan nama container.
    | Contoh lokal: http://localhost:8001
    */
    'url' => env('FORECASTING_URL', 'http://pajak_forecast_api:8000'),

    /*
    | Timeout HTTP request ke forecasting service (detik).
    | ARIMA bisa lambat untuk data besar, set cukup tinggi.
    */
    'timeout' => 60,

    /*
    | Cache hasil forecast (detik). Default 1 jam.
    | Set 0 untuk disable cache.
    */
    'cache_ttl' => 3600,
];
