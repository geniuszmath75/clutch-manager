interface PaginationMeta {
    total: number,
    page: number,
    pageSize: number;
    totalPages: number
}

interface ApiResponse<T> {
    success: boolean;
    statusCode?: number;
    errorMessage?: string;
    data?: T;
    meta?: PaginationMeta;
}

async function apiFetch<T>(url: string, options?: RequestInit): Promise<ApiResponse<T>> {
    const res = await fetch(url, {
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        ...options,
    });
    return res.json() as Promise<ApiResponse<T>>;
}

export type {
    PaginationMeta,
    ApiResponse
}
export {
    apiFetch
}