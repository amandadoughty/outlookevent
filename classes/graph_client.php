<?php

declare(strict_types=1);

namespace mod_outlookevent;
defined('MOODLE_INTERNAL') || die();

final class graph_client
{
    public function __construct(
        private readonly graph_auth $auth,
        private readonly ?\curl $curl = null
    )
    {
    }

    public function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function patch(string $path, array $data): array
    {
        return $this->request('PATCH', $path, $data);
    }

    public function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, ?array $data = null, bool $retry = true): array
    {
        $curl = $this->curl ?? new \curl();
        $curl->setHeader(['Authorization: Bearer ' . $this->auth->token(), 'Content-Type: application/json', 'Accept: application/json']);
        $url = 'https://graph.microsoft.com/v1.0/' . ltrim($path, '/');
        $body = $data === null ? [] : json_encode($data, JSON_THROW_ON_ERROR);
        $options = ['CURLOPT_TIMEOUT' => 30];
        $response = match ($method) {
            'GET' => $curl->get($url, [], $options),
            'POST' => $curl->post($url, $body, $options),
            'PATCH' => $curl->patch($url, $body, $options),
            'DELETE' => $curl->delete($url, [], $options)
        };
        if ($curl->get_errno()) throw new \moodle_exception('graphconnectionerror', 'mod_outlookevent');
        $status = (int)($curl->get_info()['http_code'] ?? 0);
        if ($status === 401 && $retry) {
            $this->auth->invalidate();
            return $this->request($method, $path, $data, false);
        }
        $result = $response === '' ? [] : json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if ($status < 200 || $status >= 300) throw new \moodle_exception('graphapierror', 'mod_outlookevent', '', null, $result['error']['message'] ?? "HTTP {$status}");
        return $result;
    }
}
