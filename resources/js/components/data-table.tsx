import { router } from '@inertiajs/react';
import {
    type ColumnDef,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    PaginationControls,
    storedPageSize,
} from '@/components/pagination-controls';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { PaginatedResponse } from '@/types';
import { EmptyState } from './empty-state';

type Props<TData> = {
    data: PaginatedResponse<TData>;
    columns: ColumnDef<TData, unknown>[];
    filters?: Record<string, string | undefined>;
    searchPlaceholder?: string;
    searchKey?: string;
    className?: string;
};

export function DataTable<TData>({
    data,
    columns,
    filters = {},
    searchPlaceholder = 'Buscar...',
    searchKey = 'search',
    className,
}: Props<TData>) {
    const [search, setSearch] = useState(filters[searchKey] ?? '');

    const rows = data?.data ?? [];

    // A listing opened without a size of its own takes the one the user chose
    // elsewhere, so the preference does not have to be repeated on every screen.
    useEffect(() => {
        const remembered = storedPageSize();

        if (
            remembered &&
            !filters.per_page &&
            data?.per_page &&
            data.per_page !== remembered
        ) {
            router.get(
                window.location.pathname,
                { ...filters, per_page: remembered },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }
    }, [filters, data]);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== (filters[searchKey] ?? '')) {
                router.get(
                    window.location.pathname,
                    {
                        ...filters,
                        [searchKey]: search || undefined,
                        page: undefined,
                    },
                    { preserveState: true, replace: true },
                );
            }
        }, 400);
        return () => clearTimeout(timeout);
    }, [search, filters, searchKey]);

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data: rows,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        pageCount: data?.last_page ?? 1,
    });

    return (
        <div className={cn('space-y-4', className)}>
            {/* Search */}
            <div className="relative max-w-sm">
                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    placeholder={searchPlaceholder}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-9"
                />
            </div>

            {/* Table */}
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-48 p-0"
                                >
                                    <EmptyState />
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <PaginationControls data={data} filters={filters} />
        </div>
    );
}
