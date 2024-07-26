<?php

namespace App\Providers;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Support\ServiceProvider;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Drive::class, function ($app) {
            $client = new GoogleClient();
            $client->setApplicationName('Test');
            $client->setScopes([Drive::DRIVE_READONLY]);
            $client->setAuthConfig(storage_path('app/credentials/google-drive-credentials.json'));
            $client->setAccessType('offline');

            // Load previously authorized credentials from a file.
            $credentialsPath = storage_path('app/credentials/google-drive-token.json');
            if (file_exists($credentialsPath)) {
                $accessToken = json_decode(file_get_contents($credentialsPath), true);
                $client->setAccessToken($accessToken);
            }

            // If there is no previous token or it's expired, refresh token.
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
            }

            return new Drive($client);
        });
    }
}
