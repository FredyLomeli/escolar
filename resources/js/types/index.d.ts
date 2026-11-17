export type UserRole = 'admin' | 'coordinator';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    role: UserRole;
    role_label: string;
    is_admin: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
