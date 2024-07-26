<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Exception;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GoogleDriveController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = $this->getClient();
    }

    private function getClient()
    {
        $clientSecretPath = storage_path('app/credentials/google-drive-credentials.json');
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check if access token is expired and refresh if necessary
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);

            // Save the updated access token if needed
            $newAccessToken = $client->getAccessToken();
            // Update your storage with $newAccessToken['access_token']
        }

        return new Google_Service_Drive($client);
    }

    public function createDriveFolder()
    {
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => 'Contracts',
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            $folder = $this->client->files->create($fileMetadata, ['fields' => 'id']);
            echo 'Folder ID: ' . $folder->id;
        } catch (Google_Service_Exception $e) {
            echo "Error creating folder: " . $e->getMessage();
        } catch (Google_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function uploadFileToDriveFolder()
    {
        $folderId = '1OC3v7JzIMR8CQsqeCIjKfEQ0RmxkN80N';
        $filePath = '/home/kiluvai/Downloads/conrcts.csv';
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => basename($filePath),
                'parents' => [$folderId]
            ]);
            $content = file_get_contents($filePath);
            $file = $this->client->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'multipart'
            ]);

            echo 'File uploaded successfully. File ID: ' . $file->id;
        } catch (Google_Service_Exception $e) {
            echo "Error uploading file: " . $e->getMessage();
        } catch (Google_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function listFilesInDriveFolder()
    {
        $folderId = '1OC3v7JzIMR8CQsqeCIjKfEQ0RmxkN80N';
        try {
            $optParams = [
                'q' => "'$folderId' in parents",
                'fields' => 'files(id, name)'
            ];
            $results = $this->client->files->listFiles($optParams);

            if (count($results->getFiles()) == 0) {
                echo "No files found in the folder.";
            } else {
                echo "Files in the folder:\n";
                foreach ($results->getFiles() as $file) {
                    echo "<pre>" . $file->getName() . ' (' . $file->getId() . ")\n" . "</pre>";
                }
            }
        } catch (Google_Service_Exception $e) {
            echo "Error listing files: " . $e->getMessage();
        } catch (Google_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function readFileFromDrive()
    {
        $fileId = '1UtGS8pJSulqvYH0QCJNCT_ZHvF14jDZPpa5GgKRyWhA';
        try {
            $file = $this->client->files->get($fileId);
            $mimeType = $file->getMimeType();

            $httpClient = new Client();

            if (strpos($mimeType, 'application/vnd.google-apps') === 0) {
                $response = $httpClient->request('GET', "https://www.googleapis.com/drive/v3/files/{$fileId}/export", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->client->getClient()->getAccessToken()['access_token']
                    ],
                    'query' => [
                        'mimeType' => 'text/plain'
                    ]
                ]);

                $content = $response->getBody()->getContents();
            } else {
                $response = $httpClient->request('GET', "https://www.googleapis.com/drive/v3/files/{$fileId}", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->client->getClient()->getAccessToken()['access_token'],
                        'Accept' => 'application/json'
                    ]
                ]);

                $content = $response->getBody()->getContents();
            }

            echo "File content:";
            echo "<br>";
            echo $content;
        } catch (Google_Service_Exception $e) {
            echo "Error reading file: " . $e->getMessage();
        } catch (Google_Exception $e) {
            echo "Error: " . $e->getMessage();
        } catch (RequestException $e) {
            echo "Guzzle HTTP RequestException: " . $e->getMessage();
        }
    }

    public function editFileInDrive()
    {
        $fileId = '1UtGS8pJSulqvYH0QCJNCT_ZHvF14jDZPpa5GgKRyWhA';

        try {
            $file = $this->client->files->get($fileId);
            $mimeType = $file->getMimeType();

            if (strpos($mimeType, 'application/vnd.google-apps') === 0) {
                $httpClient = new Client();

                $response = $httpClient->request('GET', "https://www.googleapis.com/drive/v3/files/{$fileId}/export", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->client->getClient()->getAccessToken()['access_token']
                    ],
                    'query' => [
                        'mimeType' => 'text/plain'
                    ]
                ]);

                $currentContent = $response->getBody()->getContents();
                $newContent = 'The content was updated on july 25 - 5:57 PM';
                $updatedContent = $currentContent . "\n" . $newContent;

                $updateResponse = $httpClient->request('PATCH', "https://www.googleapis.com/upload/drive/v3/files/{$fileId}?uploadType=media", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->client->getClient()->getAccessToken()['access_token'],
                        'Content-Type' => 'text/plain'
                    ],
                    'body' => $updatedContent
                ]);

                echo "File content updated successfully.\n";
            } else {
                echo "Cannot append content to binary files. Please update manually through appropriate application.\n";
            }
        } catch (Google_Service_Exception $e) {
            echo "Error updating file: " . $e->getMessage();
        } catch (Google_Exception $e) {
            echo "Error: " . $e->getMessage();
        } catch (RequestException $e) {
            echo "Guzzle HTTP RequestException: " . $e->getMessage();
        }
    }
}
