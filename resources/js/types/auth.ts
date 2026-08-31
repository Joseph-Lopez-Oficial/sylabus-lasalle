import type { User } from './models';

export type { User };

export type Auth = {
    user: User;
};

/** What every page receives, whatever it renders. */
export type SharedData = {
    name: string;
    version: string;
    auth: Auth;
    sidebarOpen: boolean;
};
