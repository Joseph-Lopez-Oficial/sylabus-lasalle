import type { SVGAttributes } from 'react';

/**
 * The system's own mark: an S over the institutional blue, crossed by the
 * golden bar of the university's palette.
 *
 * It is not the university shield, whose use its brand manual reserves to the
 * Communications Department. The colours are the official ones (#003057 and
 * #F2A900), so it sits alongside the institution without standing in for it.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect width="40" height="40" rx="9" fill="#003057" />
            <path
                d="M26.4 13.1c-1.6-1.5-3.9-2.3-6.3-2.3-4.3 0-7.2 2.2-7.2 5.5 0 2.8 1.9 4.4 5.8 5.3l2.2.5c2.2.5 3 1.1 3 2.2 0 1.4-1.4 2.3-3.6 2.3-2.3 0-4.3-.8-6-2.4l-2.3 3.2c2 1.9 4.9 2.9 8.2 2.9 4.6 0 7.6-2.3 7.6-5.9 0-2.9-1.8-4.5-5.7-5.4l-2.3-.5c-2.1-.5-3-1-3-2.1 0-1.3 1.3-2.1 3.3-2.1 1.9 0 3.6.6 5 1.9l1.3-3.1Z"
                fill="#fff"
            />
            <rect x="8.6" y="30.2" width="22.8" height="3.4" rx="1.7" fill="#F2A900" />
        </svg>
    );
}
