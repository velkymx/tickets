@props(['ticket', 'pulse'])

<div
    x-data="{
        pulse: @js($pulse),
        csrf: document.querySelector('meta[name=csrf-token]')?.content,
        refresh() {
            fetch(`/tickets/${this.pulse.id}/pulse`)
                .then(res => res.json())
                .then(data => {
                    this.pulse = data;
                });
        },
        resolve(id) {
            fetch(`/notes/${id}/resolve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ resolution_message: 'Resolved' })
            }).then(() => this.refresh());
        }
    }"
    x-init="refresh(); setInterval(() => refresh(), 30000)"
>
    @include('partials.ticket-pulse')
</div>
