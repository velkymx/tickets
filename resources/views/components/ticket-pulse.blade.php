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
        resolving: null,
        resolutionMessage: '',
        resolve(id) {
            this.resolving = id;
            this.resolutionMessage = '';
        },
        submitResolve(id) {
            if (!this.resolutionMessage.trim()) return;
            fetch(`/notes/${id}/resolve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ resolution_message: this.resolutionMessage })
            }).then(() => { this.resolving = null; this.resolutionMessage = ''; this.refresh(); location.reload(); });
        }
    }"
    x-init="refresh(); setInterval(() => refresh(), 30000)"
>
    @include('partials.ticket-pulse')
</div>
