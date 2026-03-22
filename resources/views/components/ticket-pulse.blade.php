@props(['ticket', 'pulse'])

<div
    x-data="{
        pulse: @js($pulse),
        refresh() {
            fetch(`/tickets/${this.pulse.id}/pulse`)
                .then(res => res.json())
                .then(data => {
                    this.pulse = data;
                });
        }
    }"
    x-init="refresh(); setInterval(() => refresh(), 30000)"
>
    @include('partials.ticket-pulse')
</div>
