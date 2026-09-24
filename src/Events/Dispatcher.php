<?php

namespace Darkauth\Events;

/**
 * Class Dispatcher
 * 
 * Simple event dispatcher for Darkauth components.
 */
class Dispatcher
{
    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * Register an event listener.
     *
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function listen(string $event, callable $listener)
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event.
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function dispatch(string $event, $payload = null)
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            try {
                call_user_func($listener, $payload);
            } catch (\Exception $e) {
                error_log("Darkauth event listener error [{$event}]: " . $e->getMessage());
            }
        }
    }
}
