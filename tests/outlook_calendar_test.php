<?php

declare(strict_types=1);

namespace mod_outlookevent;
defined('MOODLE_INTERNAL') || die();

/**
 * @covers \mod_outlookevent\outlook_calendar
 * @covers \mod_outlookevent\graph_client
 */
final class outlook_calendar_test extends \advanced_testcase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('mod_outlookevent', 'graphtoken')->set(sha1('tenant:client'), [
            'value' => 'test-token',
            'expires' => time() + 3600,
        ]);
    }

    private function curl_spy(): \curl
    {
        $curl = $this->getMockBuilder(\curl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHeader', 'get', 'post', 'patch', 'delete', 'get_errno', 'get_info'])
            ->getMock();
        $curl->method('get_errno')->willReturn(0);
        $curl->method('get_info')->willReturn(['http_code' => 200]);
        return $curl;
    }

    private function activity(): \stdClass
    {
        $course = $this->getDataGenerator()->create_course();
        return (object)[
            'id' => 123,
            'course' => $course->id,
            'name' => 'Teams seminar',
            'intro' => '<p>Seminar description</p>',
            'introformat' => FORMAT_HTML,
            'targetuser' => 'organiser@example.com',
            'eventstart' => 1792663200,
            'eventend' => 1792666800,
            'grapheventid' => null,
        ];
    }

    public function test_create_teams_meeting_invites_only_active_course_users(): void
    {
        $activity = $this->activity();
        $activity->eventstart = (string)$activity->eventstart;
        $activity->eventend = (string)$activity->eventend;
        $generator = $this->getDataGenerator();
        $student = $generator->create_user(['email' => 'student@example.com']);
        $teacher = $generator->create_user(['email' => 'teacher@example.com']);
        $duplicate = $generator->create_user(['email' => 'STUDENT@example.com']);
        $organiser = $generator->create_user(['email' => $activity->targetuser]);
        $suspended = $generator->create_user(['suspended' => 1]);
        foreach ([$student, $teacher, $duplicate, $organiser, $suspended] as $user) {
            $generator->enrol_user($user->id, $activity->course);
        }
        $generator->create_user(['email' => 'not-enrolled@example.com']);
        $inactive = $generator->create_user();
        $generator->enrol_user($inactive->id, $activity->course, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $expired = $generator->create_user();
        $generator->enrol_user($expired->id, $activity->course, null, 'manual', time() - 7200, time() - 3600);
        $future = $generator->create_user();
        $generator->enrol_user($future->id, $activity->course, null, 'manual', time() + 3600);

        \curl::mock_response('{"id":"graph-event"}');
        $curl = $this->curl_spy();
        $curl->expects($this->never())->method('get');
        $curl->expects($this->never())->method('patch');
        $curl->expects($this->once())->method('post')->willReturnCallback(
            function ($url, $body) use ($student, $teacher): string {
                $this->assertSame(
                    'https://graph.microsoft.com/v1.0/users/organiser%40example.com/calendar/events',
                    $url
                );
                $event = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $this->assertTrue($event['isOnlineMeeting']);
                $this->assertSame('teamsForBusiness', $event['onlineMeetingProvider']);
                $this->assertSame('Teams seminar', $event['subject']);
                $this->assertSame(['dateTime' => '2026-10-22T10:00:00', 'timeZone' => 'UTC'], $event['start']);
                $this->assertSame(['dateTime' => '2026-10-22T11:00:00', 'timeZone' => 'UTC'], $event['end']);
                $this->assertSame('mod-outlookevent-123', $event['transactionId']);
                $this->assertSame([
                    ['emailAddress' => ['address' => $student->email], 'type' => 'required'],
                    ['emailAddress' => ['address' => $teacher->email], 'type' => 'required'],
                ], $event['attendees']);
                return (new \curl())->post($url, $body, ['CURLOPT_TIMEOUT' => 30]);
            }
        );
        $calendar = new outlook_calendar(new graph_client(new graph_auth('tenant', 'client', 'secret'), $curl));
        $this->assertSame('graph-event', $calendar->sync($activity));
    }

    public function test_update_preserves_teams_details_and_refreshes_attendees(): void
    {
        $activity = $this->activity();
        $activity->grapheventid = 'graph/event';
        $activity->intro = '<p>Updated description</p>';
        $meetingdetails = '<div>Join Microsoft Teams meeting: https://teams.microsoft.com/meeting</div>';
        // Moodle consumes mocked responses in last-in, first-out order.
        \curl::mock_response('');
        \curl::mock_response(json_encode([
            'isOnlineMeeting' => true,
            'body' => [
                'contentType' => 'html',
                'content' => '<!-- mod-outlookevent-intro-start -->Old description'
                    . '<!-- mod-outlookevent-intro-end -->' . $meetingdetails,
            ],
        ]));
        $curl = $this->curl_spy();
        $url = 'https://graph.microsoft.com/v1.0/users/organiser%40example.com/events/graph%2Fevent';
        $curl->expects($this->never())->method('post');
        $curl->expects($this->once())->method('get')->with($url, [], ['CURLOPT_TIMEOUT' => 30])
            ->willReturnCallback(fn($url, $params, $options) => (new \curl())->get($url, $params, $options));
        $curl->expects($this->once())->method('patch')->willReturnCallback(
            function ($requesturl, $body) use ($url, $meetingdetails): string {
                $this->assertSame($url, $requesturl);
                $event = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame([], $event['attendees']);
                $this->assertTrue($event['isOnlineMeeting']);
                $this->assertStringContainsString('Updated description', $event['body']['content']);
                $this->assertStringNotContainsString('Old description', $event['body']['content']);
                $this->assertStringContainsString($meetingdetails, $event['body']['content']);
                $this->assertArrayNotHasKey('transactionId', $event);
                return (new \curl())->patch($requesturl, $body, ['CURLOPT_TIMEOUT' => 30]);
            }
        );
        $calendar = new outlook_calendar(new graph_client(new graph_auth('tenant', 'client', 'secret'), $curl));
        $this->assertSame('graph/event', $calendar->sync($activity));
    }

    public function test_create_rejects_missing_event_id(): void
    {
        $activity = $this->activity();
        \curl::mock_response('{}');
        $calendar = new outlook_calendar(new graph_client(new graph_auth('tenant', 'client', 'secret')));
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('graphinvalidresponse', 'mod_outlookevent'));
        $calendar->sync($activity);
    }
}
