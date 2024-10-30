<?php

namespace Controllers;

class BlacklistChecker
{
    private $apiKey;
    private $apiUrl = 'https://api.abuseipdb.com/api/v2/check';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function isIpBlacklisted(string $ip): bool
    {
        $cacheFile = '/tmp/blacklist_cache_' . md5($ip);
        $cacheTime = 3600;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            return file_get_contents($cacheFile) === 'true';
        }

        $url = $this->apiUrl . '?ipAddress=' . urlencode($ip);
        $headers = [
            'Key: ' . $this->apiKey,
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }

        $data = json_decode($response, true);
        $isBlacklisted = isset($data['data']['abuseConfidenceScore']) && $data['data']['abuseConfidenceScore'] > 0;

        file_put_contents($cacheFile, $isBlacklisted ? 'true' : 'false');

        return $isBlacklisted;
    }

    /**
     * @param array $ips
     * @return array
     */
    public function getBlacklistedIps(array $ips): array
    {
        $blacklistedIps = [];

        foreach ($ips as $ip) {
            if ($this->isIpBlacklisted($ip)) {
                $blacklistedIps[] = $ip;
            }
        }

        return $blacklistedIps;
    }
}