function formatDate(iso: string): string {
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleString('en-US', {
        month: 'long', day: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: false,
    });
}

export {
    formatDate
}