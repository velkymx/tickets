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
        submitting: false,
        resolve(id) {
            this.resolving = id;
            this.resolutionMessage = '';
        },
        async submitResolve(id) {
            if (!this.resolutionMessage.trim() || this.submitting) return;
            this.submitting = true;
            try {
                const res = await fetch(`/notes/${id}/resolve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ resolution_message: this.resolutionMessage })
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || 'Could not resolve. Check permissions.');
                    this.submitting = false;
                    return;
                }
                location.reload();
            } catch (e) {
                alert('Network error. Please try again.');
                this.submitting = false;
            }
        }
    }"
    x-init="refresh(); setInterval(() => refresh(), 30000)"
>
    @include('partials.ticket-pulse')
</div>
