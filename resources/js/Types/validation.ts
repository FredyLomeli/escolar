export type FindingSeverity = 'error' | 'warning' | 'info';

export interface FindingEntity {
    type: string;
    id: number;
    label: string;
}

export interface ValidationFinding {
    code: string;
    severity: FindingSeverity;
    message: string;
    section_ids: number[];
    entity: FindingEntity | null;
    suggestion: Record<string, unknown> | null;
}

export interface ValidationCounts {
    errors: number;
    warnings: number;
    infos: number;
}
