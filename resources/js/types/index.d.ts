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

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash?: FlashMessages;
};
