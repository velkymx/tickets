<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Update</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8f9fa;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8f9fa;">
        <tr>
            <td align="center" style="padding: 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #0d6efd; padding: 24px; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">Tickets</h1>
                        </td>
                    </tr>
                    
                    {{-- Content --}}
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; color: #6c757d; font-size: 14px;">
                                {{ $ticket->user->name }} updated a ticket
                            </p>
                            
                            <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #212529;">
                                <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" style="color: #0d6efd; text-decoration: none;">
                                    #{{ $ticket->id }} {{ $ticket->subject }}
                                </a>
                            </h2>
                            
                            {{-- Note Body --}}
                            @if($ticket->notes()->where('ticket_id', $ticket->id)->orderBy('created_at', 'desc')->first())
                                @php
                                    $latestNote = $ticket->notes()->where('ticket_id', $ticket->id)->orderBy('created_at', 'desc')->first();
                                @endphp
                                <div style="background-color: #f8f9fa; border-left: 4px solid #0d6efd; padding: 16px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                                    {!! clean($latestNote->body ?? '') !!}
                                </div>
                            @endif
                            
                            {{-- Ticket Details --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 24px;">
                                <tr>
                                    <td style="padding: 12px 0; border-top: 1px solid #dee2e6;">
                                        <strong style="color: #6c757d; font-size: 12px; text-transform: uppercase;">Importance</strong><br>
                                        <span style="font-size: 14px; color: #212529;">{{ $ticket->importance->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-top: 1px solid #dee2e6;">
                                        <strong style="color: #6c757d; font-size: 12px; text-transform: uppercase;">Status</strong><br>
                                        <span style="font-size: 14px; color: #212529;">{{ $ticket->status->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-top: 1px solid #dee2e6;">
                                        <strong style="color: #6c757d; font-size: 12px; text-transform: uppercase;">Assignee</strong><br>
                                        <span style="font-size: 14px; color: #212529;">{{ $ticket->assignee->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-top: 1px solid #dee2e6;">
                                        <strong style="color: #6c757d; font-size: 12px; text-transform: uppercase;">Type</strong><br>
                                        <span style="font-size: 14px; color: #212529;">{{ $ticket->type->name }}</span>
                                    </td>
                                </tr>
                            </table>
                            
                            {{-- View Ticket Button --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" style="display: inline-block; background-color: #0d6efd; color: #ffffff; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: 600;">
                                            View Ticket
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 16px 24px; border-radius: 0 0 8px 8px; border-top: 1px solid #dee2e6;">
                            <p style="margin: 0; color: #6c757d; font-size: 12px; text-align: center;">
                                Notification generated using <a href="{{ config('app.url') }}" style="color: #0d6efd;">Tickets</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
