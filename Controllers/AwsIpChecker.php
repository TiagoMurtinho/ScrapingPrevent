<?php

namespace Controllers;

use Redis;

class AwsIpChecker
{
    private $awsIpRanges = [];
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
        $this->awsIpRanges = $this->loadAwsIpRanges();
    }

    private function loadAwsIpRanges(): array
    {
        $url = 'https://ip-ranges.amazonaws.com/ip-ranges.json';
        $json = file_get_contents($url);
        $data = json_decode($json, true);

        return array_column($data['prefixes'], 'ip_prefix');
    }

    public function isAwsIp($ip): bool
    {
        foreach ($this->awsIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    public function isAwsIpMarked($ip): bool
    {
        $key = "aws_offending_ips:$ip";
        return $this->redis->get($key) !== false;
    }

    public function markAwsIpAsOffending($offendingIp)
    {
        foreach ($this->awsIpRanges as $range) {
            if ($this->ipInRange($offendingIp, $range)) {
                list($subnet, $bits) = explode('/', $range);

                $startIp = ip2long($subnet);
                $endIp = $startIp + (1 << (32 - $bits)) - 1;

                for ($ip = $startIp; $ip <= $endIp; $ip++) {
                    $ipString = long2ip($ip);
                    $key = "aws_offending_ips:$ipString";
                    $this->redis->set($key, true, 3600);
                }

                break;
            }
        }
    }

    private function ipInRange($ip, $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }

    public function unmarkAwsIp($ipToUnblock)
    {
        if ($this->isAwsIp($ipToUnblock)) {
            $key = "aws_offending_ips:$ipToUnblock";
            $this->redis->del($key);
            echo "IP $ipToUnblock foi desbloqueado.<br>";
        }
    }
}