import axios from 'axios';
import { AlertCircle, Check, Download } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Props = {
    /** The route the file comes from. */
    href: string;
    children: React.ReactNode;
    /** Name for the saved file when the response does not state one. */
    fileName?: string;
    variant?: React.ComponentProps<typeof Button>['variant'];
    size?: React.ComponentProps<typeof Button>['size'];
    className?: string;
    icon?: React.ReactNode;
};

/** How long the outcome stays on the button before it goes back to normal. */
const FEEDBACK_MS = 3000;

/**
 * Reads the name the server gave the file.
 *
 * Both `filename*=UTF-8''…` and plain `filename=…` are accepted, since the
 * first carries accents correctly and is the one Laravel sends when it has to.
 */
function fileNameFromDisposition(disposition: string | undefined): string | null {
    if (!disposition) {
        return null;
    }

    const encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (encoded) {
        try {
            return decodeURIComponent(encoded[1]);
        } catch {
            // A malformed header should not cost the user their download.
        }
    }

    const plain = disposition.match(/filename="?([^";]+)"?/i);

    return plain ? plain[1] : null;
}

/**
 * A download that says it is working on it.
 *
 * The reports are built on request and a large group takes its time, so a plain
 * link leaves the screen silent and the user clicks again, which starts the
 * whole thing a second time. Fetching the file ourselves is what makes the wait
 * visible: the button knows when the file actually arrived, and when it failed.
 */
export function DownloadButton({
    href,
    children,
    fileName,
    variant = 'outline',
    size,
    className,
    icon,
}: Props) {
    const [state, setState] = useState<'idle' | 'loading' | 'done' | 'error'>(
        'idle',
    );
    const [error, setError] = useState<string | null>(null);
    const timeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (timeout.current) {
                clearTimeout(timeout.current);
            }
        },
        [],
    );

    /** Shows the outcome for a moment, then returns the button to normal. */
    function settle(next: 'done' | 'error') {
        setState(next);

        if (timeout.current) {
            clearTimeout(timeout.current);
        }

        timeout.current = setTimeout(() => setState('idle'), FEEDBACK_MS);
    }

    async function download() {
        if (state === 'loading') {
            return;
        }

        setState('loading');
        setError(null);

        try {
            const response = await axios.get(href, { responseType: 'blob' });

            const name =
                fileNameFromDisposition(
                    response.headers['content-disposition'],
                ) ??
                fileName ??
                'descarga.xlsx';

            const url = URL.createObjectURL(response.data);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = name;
            anchor.click();
            URL.revokeObjectURL(url);

            settle('done');
        } catch (err: unknown) {
            // The error body arrives as a blob like any other response, so it
            // has to be read back as text before it says anything useful.
            const response = (err as { response?: { data?: Blob } })?.response;
            let message = 'No se pudo generar el archivo.';

            if (response?.data instanceof Blob) {
                try {
                    const text = await response.data.text();
                    message = JSON.parse(text)?.message ?? message;
                } catch {
                    // Not JSON: the generic message is the honest one.
                }
            }

            setError(message);
            settle('error');
        }
    }

    return (
        <div className="inline-flex flex-col items-start gap-1">
            <Button
                variant={variant}
                size={size}
                className={className}
                onClick={download}
                disabled={state === 'loading'}
                aria-busy={state === 'loading'}
            >
                {state === 'loading' && <Spinner className="mr-2 h-4 w-4" />}
                {state === 'done' && (
                    <Check className="mr-2 h-4 w-4 text-green-600" />
                )}
                {state === 'error' && (
                    <AlertCircle className="mr-2 h-4 w-4 text-destructive" />
                )}
                {state === 'idle' &&
                    (icon ?? <Download className="mr-2 h-4 w-4" />)}
                {state === 'loading' ? 'Generando archivo...' : children}
            </Button>

            {state === 'error' && error && (
                <span className={cn('text-xs text-destructive')}>{error}</span>
            )}
        </div>
    );
}
