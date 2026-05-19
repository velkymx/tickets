<?php

return [

    'triggers' => [

        'ticket.created' => [
            'label' => 'Ticket Created',
            'event' => \App\Events\TicketCreated::class,
            'context_builder' => \App\Automations\ContextBuilders\TicketCreatedContextBuilder::class,
        ],

        'ticket.updated' => [
            'label' => 'Ticket Updated',
            'event' => \App\Events\TicketUpdated::class,
            'context_builder' => \App\Automations\ContextBuilders\TicketUpdatedContextBuilder::class,
        ],

    ],

    'operators' => [
        'equals', 'not_equals', 'contains', 'starts_with', 'ends_with',
        'greater_than', 'less_than', 'in', 'not_in', 'is_empty', 'is_not_empty', 'changed',
    ],

    'action_types' => [
        'call_webhook' => 'Send Webhook',
        'call_api' => 'Call API',
        'send_email' => 'Send Email',
    ],

];
