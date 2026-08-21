"use client";

import {
  DataTableFilterableColumn,
  DataTableSearchableColumn,
} from "@/types/table";
import {
  ColumnDef,
  ColumnFiltersState,
  PaginationState,
  VisibilityState,
  SortingState,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  useReactTable,
} from "@tanstack/react-table";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useDebounce } from "./use-debounce";

interface useDataTableProps<TData, TValue> {
  data: TData[];
  columns: ColumnDef<TData, TValue>[];
  pageCount?: number;
  searchableColumns?: DataTableSearchableColumn<TData>[];
  filterableColumns?: DataTableFilterableColumn<TData>[];
}

export function useDataTable<TData, TValue>({
  data,
  columns,
  pageCount,
  searchableColumns = [],
  filterableColumns = [],
}: useDataTableProps<TData, TValue>) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  // search params
  const page = searchParams?.get("page") ?? "1";
  const pageAsNumber = Number(page);
  const fallbackPage =
    isNaN(pageAsNumber) || pageAsNumber < 1 ? 1 : pageAsNumber;

  const limit = searchParams?.get("limit") ?? "10";
  const limitAsNumber = Number(limit);
  const fallbackLimit = isNaN(limitAsNumber) ? 10 : limitAsNumber;

  const sortColumn = searchParams?.get("sortcolumn") ?? "";
  const sortDir = searchParams?.get("sortdir") ?? "0";

  const globalSearch = searchParams?.get("search") ?? "";
  // create query string
  const createQueryString = useCallback(
    (params: Record<string, string | number | null>) => {
      const newSearchParams = new URLSearchParams(searchParams?.toString());

      // Remove existing page and limit if they are default values
      if (newSearchParams.get("page") === "1") {
        newSearchParams.delete("page");
      }
      if (newSearchParams.get("limit") === "10") {
        newSearchParams.delete("limit");
      }

      for (const [key, value] of Object.entries(params)) {
        // Only add page and limit to URL if they're not default values
        if (key === "page" && value === 1) {
          newSearchParams.delete(key);
          continue;
        }
        if (key === "limit" && value === 10) {
          newSearchParams.delete(key);
          continue;
        }

        if (value === null) {
          newSearchParams.delete(key);
        } else {
          newSearchParams.set(key, String(value));
        }
      }

      return newSearchParams.toString();
    },
    [searchParams]
  );

  // Initial column filters
  const initialColumnFilters: ColumnFiltersState = useMemo(() => {
    const filters: ColumnFiltersState = [];

    // Add filters for filterable columns
    Array.from(searchParams.entries()).forEach(([key, value]) => {
      const filterableColumn = filterableColumns.find(
        (column) => column.id === key
      );
      if (filterableColumn) {
        filters.push({ id: key, value: value.split(".") });
      }
    });

    // Add global search to all searchable columns
    if (globalSearch && searchableColumns.length > 0) {
      searchableColumns.forEach((column) => {
        filters.push({
          id: String(column.id),
          value: globalSearch,
        });
      });
    }

    return filters;
  }, [filterableColumns, searchableColumns, searchParams, globalSearch]);

  // Table states
  const [rowSelection, setRowSelection] = useState({});
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});
  const [columnFilters, setColumnFilters] =
    useState<ColumnFiltersState>(initialColumnFilters);

  // handle server-side pagination
  const [{ pageIndex, pageSize }, setPagination] = useState<PaginationState>({
    pageIndex: fallbackPage - 1,
    pageSize: fallbackLimit,
  });

  const pagination = useMemo(
    () => ({ pageIndex, pageSize }),
    [pageIndex, pageSize]
  );

  useEffect(() => {
    setPagination({ pageIndex: fallbackPage - 1, pageSize: fallbackLimit });
  }, [fallbackPage, fallbackLimit]);

  useEffect(() => {
    const newPage = pageIndex + 1;
    const queryString = createQueryString({
      page: newPage,
      limit: pageSize,
    });

    router.push(`${pathname}${queryString ? `?${queryString}` : ""}`, {
      scroll: false,
    });

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pageIndex, pageSize]);

  const [sorting, setSorting] = useState<SortingState>([
    { id: sortColumn, desc: sortDir === "1" },
  ]);

  useEffect(() => {
    const queryString = createQueryString({
      page,
      sortcolumn: sorting[0]?.id,
      sortdir: sorting[0]?.desc ? "1" : "0",
    });

    router.push(`${pathname}${queryString ? `?${queryString}` : ""}`, {
      scroll: false,
    });

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sorting]);

  // Handle server-side filtering
  const debouncedSearchableColumnFilters = JSON.parse(
    useDebounce(
      JSON.stringify(
        columnFilters.filter((filter) => {
          return searchableColumns.find((column) => column.id === filter.id);
        })
      ),
      500
    )
  ) as ColumnFiltersState;

  const filterableColumnFilters = columnFilters.filter((filter) => {
    return filterableColumns.find((column) => column.id === filter.id);
  });

  useEffect(() => {
    // Initialize new params
    const newParamsObject: Record<string, string | number | null> = {
      page: 1,
      limit: 10,
    };

    // Handle debounced searchable column filters
    for (const column of debouncedSearchableColumnFilters) {
      if (typeof column.value === "string") {
        Object.assign(newParamsObject, {
          [column.id]: typeof column.value === "string" ? column.value : null,
        });
      }
    }

    // Handle filterable column filters
    for (const column of filterableColumnFilters) {
      if (typeof column.value === "object" && Array.isArray(column.value)) {
        Object.assign(newParamsObject, { [column.id]: column.value.join(".") });
      }
    }

    // Remove deleted values
    for (const key of searchParams.keys()) {
      if (
        (searchableColumns.find((column) => column.id === key) &&
          !debouncedSearchableColumnFilters.find(
            (column) => column.id === key
          )) ||
        (filterableColumns.find((column) => column.id === key) &&
          !filterableColumnFilters.find((column) => column.id === key))
      ) {
        Object.assign(newParamsObject, { [key]: null });
      }
    }

    // After cumulating all the changes, push new params
    const queryString = createQueryString(newParamsObject);
    router.push(`${pathname}${queryString ? `?${queryString}` : ""}`, {
      scroll: false,
    });

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [
    // eslint-disable-next-line react-hooks/exhaustive-deps
    JSON.stringify(debouncedSearchableColumnFilters),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    JSON.stringify(filterableColumnFilters),
  ]);

  const dataTable = useReactTable({
    data,
    columns,
    pageCount: pageCount ?? -1,
    state: {
      pagination,
      sorting,
      columnVisibility,
      rowSelection,
      columnFilters,
    },
    enableRowSelection: true,
    onRowSelectionChange: setRowSelection,
    onPaginationChange: setPagination,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
  });

  return { dataTable };
}
