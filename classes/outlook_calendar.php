<?php

declare(strict_types=1);

namespace mod_outlookevent;
defined('MOODLE_INTERNAL') || die();

final class outlook_calendar
{
    private const INTRO_START = '<!-- mod-outlookevent-intro-start -->';
    private const INTRO_END = '<!-- mod-outlookevent-intro-end -->';

    public function __construct(private readonly graph_client $graph)
    {
    }

    public static function create(): self
    {
        return new self(new graph_client(graph_auth::from_config()));
    }

    public function sync(\stdClass $activity): string
    {
        $intro = self::INTRO_START
            . format_text($activity->intro ?? '', $activity->introformat ?? FORMAT_HTML)
            . self::INTRO_END;
        $event = [
            'subject' => $activity->name,
            'body' => ['contentType' => 'HTML', 'content' => $intro],
            'start' => ['dateTime' => gmdate('Y-m-d\TH:i:s', (int)$activity->eventstart), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => gmdate('Y-m-d\TH:i:s', (int)$activity->eventend), 'timeZone' => 'UTC'],
            'isOnlineMeeting' => true,
            'onlineMeetingProvider' => 'teamsForBusiness',
            'attendees' => $this->attendees($activity),
        ];
        $user = rawurlencode($activity->targetuser);
        if (!empty($activity->grapheventid)) {
            $path = "users/{$user}/events/" . rawurlencode($activity->grapheventid);
            $existing = $this->graph->get($path);
            if (!empty($existing['isOnlineMeeting'])) {
                // Graph requires the Teams meeting details in the body to survive updates.
                $body = $existing['body']['content'] ?? '';
                $start = strpos($body, self::INTRO_START);
                $end = strpos($body, self::INTRO_END);
                if ($start !== false && $end !== false && $end >= $start) {
                    $event['body']['content'] = substr_replace(
                        $body,
                        $intro,
                        $start,
                        $end + strlen(self::INTRO_END) - $start
                    );
                } else {
                    $event['body']['content'] = $intro . $body;
                }
            }
            $this->graph->patch($path, $event);
            return $activity->grapheventid;
        }
        $event['transactionId'] = 'mod-outlookevent-' . $activity->id;
        $result = $this->graph->post("users/{$user}/calendar/events", $event);
        if (empty($result['id'])) throw new \moodle_exception('graphinvalidresponse', 'mod_outlookevent');
        return $result['id'];
    }

    private function attendees(\stdClass $activity): array
    {
        $users = get_enrolled_users(
            \context_course::instance($activity->course),
            '',
            0,
            'u.id, u.email, u.suspended',
            'u.id',
            0,
            0,
            true
        );
        $attendees = [];
        foreach ($users as $user) {
            if ($user->suspended) {
                continue;
            }
            if (!validate_email($user->email)) {
                throw new \moodle_exception('invalidattendeeemail', 'mod_outlookevent', '', $user->id);
            }
            $email = \core_text::strtolower($user->email);
            if ($email === \core_text::strtolower($activity->targetuser) || isset($attendees[$email])) {
                continue;
            }
            $attendees[$email] = [
                'emailAddress' => ['address' => $user->email],
                'type' => 'required',
            ];
        }
        return array_values($attendees);
    }

    public function delete(string $user, string $eventid): void
    {
        $this->graph->delete('users/' . rawurlencode($user) . '/events/' . rawurlencode($eventid));
    }
}
