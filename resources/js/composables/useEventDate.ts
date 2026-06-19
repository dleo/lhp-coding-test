const formatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZoneName: 'short',
});

export function formatEventDate(unixSeconds: number): string {
    return formatter.format(new Date(unixSeconds * 1000));
}

export function resolvedTimeZone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}
