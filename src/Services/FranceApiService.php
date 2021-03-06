<?php
namespace App\Services;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FranceApiService
{
    private $session;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $metadataUrl;

    public function __construct(SessionInterface $session)
    {
        $this->session      = $session;
        $this->clientId     = $_ENV['CLIENT_ID'];
        $this->clientSecret = $_ENV['CLIENT_SECRET'];
        $this->redirectUri  = $_ENV['REDIRECT_URI'];
        $this->metadataUrl  = $_ENV['METADATA_URL'];
    }
    

    public function buildAuthorizeUrl()
    {
        $this->session->set('state', bin2hex(random_bytes(5)));
        $this->session->set('nonce', bin2hex(random_bytes(5)));
        $metadata = $this->httpRequest($this->metadataUrl);
        $url = $metadata->authorization_endpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'openid email profile ',
            'state' => $this->session->get('state'),
            'nonce' => $this->session->get('nonce')
        ]);
        return $url;
    }

    public function authorizeUser()
    {
        if ($this->session->get('state') != $_GET['state']) {
            return null;
        }

        if (isset($_GET['error'])) {
            return null;
        }

        $metadata = $this->httpRequest($this->metadataUrl);

        $response = $this->httpRequest($metadata->token_endpoint, [
            'grant_type' => 'authorization_code',
            'code' => $_GET['code'],
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        if (!isset($response->id_token)) {
            return null;
        }

        $this->session->set('id_token', $response->id_token);

        $claims = json_decode(base64_decode(explode('.', $response->id_token)[1]));

        return $claims;
    }

    private function httpRequest($url, $params = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        return json_decode(curl_exec($ch));
    }

    public function logoutURL()
    {
        
        $params = [
            'post_logout_redirect_uri' => $this->redirectUri,
            ]; 
        return $this->metadataUrl.'logout?'.http_build_query($params);
    }
}