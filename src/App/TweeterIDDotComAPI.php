<?php

namespace App;

use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TweeterIDDotComAPI
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function getTwitterID(string $username): string
    {
        error_log('APIIIII');
        $formData = new FormDataPart([
            'input' => $username,
        ]);
        $response = $this->httpClient->request('POST', 'https://tweeterid.com/ajax.php', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToString(),
        ]);

        return trim($response->getContent());
    }
}