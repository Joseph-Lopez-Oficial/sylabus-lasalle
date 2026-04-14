import type { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

// ── Date formatting ───────────────────────────────────────────────────────────

export function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '—';
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(dateString));
}

export function formatDateTime(dateString: string | null | undefined): string {
    if (!dateString) return '—';
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
}

// ── Number formatting ─────────────────────────────────────────────────────────

export function formatDecimal(value: number, decimals = 1): string {
    return value.toFixed(decimals);
}

export function formatPercent(value: number): string {
    return `${value.toFixed(1)}%`;
}

export function calcAverage(values: number[]): number {
    if (values.length === 0) return 0;
    return values.reduce((sum, v) => sum + v, 0) / values.length;
}
