import * as React from "react";
import type {
  DataTableFilterableColumn,
  DataTableSearchableColumn,
} from "@/types/table";
import {
  flexRender,
  type ColumnDef,
  type Table as TanstackTable,
} from "@tanstack/react-table";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

import { DataTableFloatingBar } from "../data-table-floating-bar";
import { DataTablePagination } from "../data-table-pagination";
import { DataTableToolbar } from "../data-table-toolbar";
import { AlertCircle, CheckCircle2 } from "lucide-react";

interface DataTableProps<TData, TValue> {
  dataTable: TanstackTable<TData>;
  columns: ColumnDef<TData, TValue>[];
  searchableColumns?: DataTableSearchableColumn<TData>[];
  filterableColumns?: DataTableFilterableColumn<TData>[];
  advancedFilter?: boolean;
  floatingBarContent?: React.ReactNode | null;
  columnLabels?: Record<string, string>;
  newRowLink?: string;
  deleteRowsAction?: React.MouseEventHandler<HTMLButtonElement>;
}

export function DataTableException<TData, TValue>({
  dataTable,
  columns,
  searchableColumns = [],
  filterableColumns = [],
  advancedFilter = false,
  columnLabels,
  newRowLink,
  floatingBarContent,
  deleteRowsAction,
}: DataTableProps<TData, TValue>) {
  return (
    <div className="space-y-4">
      <DataTableToolbar
        table={dataTable}
        filterableColumns={filterableColumns}
        searchableColumns={searchableColumns}
        deleteRowsAction={deleteRowsAction}
        columnLabels={columnLabels}
        newRowLink={newRowLink}
      />
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {dataTable.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {dataTable.getRowModel().rows?.length ? (
              dataTable.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length}>
                  <div className="flex flex-col items-center p-6 space-y-2 text-center">
                    <CheckCircle2 className="w-12 h-12 text-green-500" />
                    <span className="text-gray-600 dark:text-white">
                      Currently, there is no exception !
                    </span>
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <div className="space-y-2.5">
        <DataTablePagination table={dataTable} />
        {floatingBarContent ? (
          <DataTableFloatingBar table={dataTable}>
            {floatingBarContent}
          </DataTableFloatingBar>
        ) : null}
      </div>
    </div>
  );
}
