<?php

namespace Darkauth\Audit;

use Darkauth\Events\Dispatcher;

/**
 * Class AuditLogger
 * 
 * Handles logging of security-related events.
 */
class AuditLogger
{
    /**
     * @var callable
     */
    protected $storageCallback;

    /**
     * AuditLogger constructor.
     *
     * @param callable $storageCallback
     */
    public function __construct(callable $storageCallback)
    {
        $this->storageCallback = $storageCallback;
    }

    /**
     * Subscribe to events and log them.
     *
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function subscribe(Dispatcher $dispatcher)
    {
        $events = [
            'auth.login.success',
            'auth.login.failed',
            'auth.logout',
            'auth.mfa.success',
            'auth.mfa.failed',
            'auth.password.reset',
            'auth.risk.detected'
        ];

        foreach ($events as $event) {
            $dispatcher->listen($event, function($payload) use ($event) {
                $this->log($event, $payload);
            });
        }
    }

    /**
     * Log an event.
     *
     * @param string $event
     * @param array $data
     * @return void
     */
    public function log(string $event, array $data = [])
    {
        $logData = [
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => json_encode($data)
        ];

        call_user_func($this->storageCallback, $logData);
    }
}
