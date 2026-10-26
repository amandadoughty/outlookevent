<?php

declare(strict_types=1);

namespace mod_outlookevent;
defined('MOODLE_INTERNAL') || die();

final class graph_auth
{
    private const BUFFER = 300;

    public function __construct(private readonly string $tenantid, private readonly string $clientid, private readonly string $secret)
    {
    }

    public static function from_config(): self
    {
        $c = get_config('mod_outlookevent');
        if (empty($c->tenantid) || empty($c->clientid) || empty($c->clientsecret)) throw new \moodle_exception('graphnotconfigured', 'mod_outlookevent');
        return new self($c->tenantid, $c->clientid, $c->clientsecret);
    }

    public function token(): string
    {
        global $CFG;

        $cache = \cache::make('mod_outlookevent', 'graphtoken');
        $key = sha1($this->tenantid . ':' . $this->clientid);
        $token = $cache->get($key);
        if (is_array($token) && ($token['expires'] ?? 0) > time() + self::BUFFER) return $token['value'];
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $response = $curl->post('https://login.microsoftonline.com/' . rawurlencode($this->tenantid) . '/oauth2/v2.0/token', [
            'client_id' => $this->clientid, 'client_secret' => $this->secret,
            'scope' => 'https://graph.microsoft.com/.default', 'grant_type' => 'client_credentials',
        ]);
        $status = (int)($curl->get_info()['http_code'] ?? 0);
        $data = json_decode($response, true);
        if ($curl->get_errno() || $status < 200 || $status >= 300 || empty($data['access_token']) || empty($data['expires_in'])) throw new \moodle_exception('graphautherror', 'mod_outlookevent');
        $token = ['value' => $data['access_token'], 'expires' => time() + (int)$data['expires_in']];
        $cache->set($key, $token);
        return $token['value'];
    }

    public function invalidate(): void
    {
        \cache::make('mod_outlookevent', 'graphtoken')->delete(sha1($this->tenantid . ':' . $this->clientid));
    }
}
