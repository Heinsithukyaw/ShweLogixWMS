<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The event timestamp.
     *
     * @var \Carbon\Carbon
     */
    public $timestamp;

    /**
     * The event version.
     *
     * @var string
     */
    public $version = '1.0';

    /**
     * The event source.
     *
     * @var string
     */
    public $source;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->timestamp = now();
        $this->source = config('app.name');
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get the event payload.
     *
     * @return array
     */
    abstract public function getPayload(): array;

    /**
     * Convert the event to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'timestamp' => $this->timestamp->toIso8601String(),
            'version' => $this->version,
            'source' => $this->source,
            'payload' => $this->getPayload(),
        ];
    }
}