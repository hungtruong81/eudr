"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTrigger,
} from "@/components/ui/sheet";
import { Inbox } from "lucide-react";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback } from "react";
import { debounce } from "lodash";
import { ReactNode } from "react";
import Pagination from "./pagination";

// Types
interface FilterOption {
  value: string;
  label: string;
}

interface Filter {
  key: string;
  placeholder: string;
  options: FilterOption[];
  value: string;
  onChange: (value: string) => void;
}

interface ActionButton {
  icon: ReactNode;
  tooltip: string;
  onClick: () => void;
  variant?: "default" | "outline" | "secondary" | "destructive";
  disabled?: boolean;
  className?: string;
}

interface HeaderColumn {
  label: string;
  className?: string;
}

interface GenericTableProps<T = any> {
  // Data
  data?: {
    records: T[];
    total_pages: number;
  };
  isLoading?: boolean;

  // Headers
  title?: string;
  headers: HeaderColumn[];

  // Search
  searchPlaceholder?: string;
  searchEnabled?: boolean;

  // Filters
  filters?: Filter[];
  customFilters?: ReactNode; // For complex filters like date pickers

  // Actions
  createButton?: {
    label: string;
    onClick: () => void;
  };

  // Row rendering
  renderCard: (item: T) => {
    title: string;
    subtitle?: string;
    content: ReactNode;
    status?: {
      variant: "default" | "outline" | "secondary" | "destructive";
      label: string;
    };
    actions: ActionButton[];
  };

  // Pagination
  page: number;
  limit: number;
  onPageChange: (page: number) => void;

  // Mobile filters
  mobileFiltersEnabled?: boolean;

  // Empty state
  emptyStateMessage?: string;
}

export function GenericTable<T = any>({
  data,
  isLoading,
  title,
  headers,
  searchPlaceholder = "Tìm kiếm...",
  searchEnabled = true,
  filters = [],
  customFilters,
  createButton,
  renderCard,
  page,
  limit,
  onPageChange,
  mobileFiltersEnabled = true,
  emptyStateMessage = "Không có dữ liệu",
}: GenericTableProps<T>) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const search = searchParams?.get("search") ?? "";

  // Search handler
  const handleSearch = useCallback(
    debounce((value: string) => {
      const params = new URLSearchParams(searchParams as any);
      if (value) {
        params.set("search", value);
      } else {
        params.delete("search");
      }
      params.set("page", "1");
      router.push(`?${params.toString()}`);
    }, 500),
    [searchParams, router]
  );

  // Page change handler
  const handlePageChange = (newPage: number) => {
    const params = new URLSearchParams(searchParams as any);
    params.set("page", newPage.toString());
    router.push(`?${params.toString()}`);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  // Clear all filters
  const handleClearFilters = () => {
    const params = new URLSearchParams();
    params.set("limit", limit.toString());
    router.push(`?${params.toString()}`);
  };

  // Check if any filters are active
  const hasActiveFilters = filters.some(
    (filter) => filter.value && filter.value !== "all" && filter.value !== ""
  );

  const renderMobileFilters = () => (
    <Sheet>
      <SheetTrigger asChild>
        <Button variant="outline" className="w-full">
          Bộ lọc
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom" className="h-[60vh] p-4">
        <SheetHeader></SheetHeader>
        <div className="space-y-2">
          {customFilters}
          {filters.map((filter, index) => (
            <Select
              key={index}
              value={filter.value}
              onValueChange={filter.onChange}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder={filter.placeholder} />
              </SelectTrigger>
              <SelectContent>
                {filter.options.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          ))}
          {hasActiveFilters && (
            <Button
              variant="outline"
              className="w-full mt-4"
              onClick={handleClearFilters}
            >
              Xóa bộ lọc
            </Button>
          )}
        </div>
      </SheetContent>
    </Sheet>
  );

  const renderDesktopFilters = () => (
    <div className="flex flex-row gap-2">
      {customFilters}
      {filters.map((filter, index) => (
        <Select
          key={index}
          value={filter.value}
          onValueChange={filter.onChange}
        >
          <SelectTrigger size="default" className="text-sm sm:text-base">
            <SelectValue placeholder={filter.placeholder} />
          </SelectTrigger>
          <SelectContent>
            {filter.options.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      ))}
      {hasActiveFilters && (
        <Button
          variant="outline"
          onClick={handleClearFilters}
          className="text-sm sm:text-base"
        >
          Xóa bộ lọc
        </Button>
      )}
    </div>
  );

  return (
    <div className="min-h-screen flex flex-col space-y-2">
      {/* Title */}
      {title && <h1 className="text-2xl font-bold mb-4">{title}</h1>}

      {/* Create Button Row */}
      {createButton && (
        <div className="flex justify-end">
          <Button
            className="sm:w-auto text-sm sm:text-base py-2"
            onClick={createButton.onClick}
          >
            {createButton.label}
          </Button>
        </div>
      )}

      {/* Search and Filters */}
      <div className="flex justify-between items-center gap-2">
        {/* Mobile Filters */}
        {mobileFiltersEnabled && (filters.length > 0 || customFilters) && (
          <div className="md:hidden">{renderMobileFilters()}</div>
        )}

        {/* Search */}
        <div className="flex flex-row gap-2 flex-1 md:flex-initial">
          {searchEnabled && (
            <Input
              type="text"
              placeholder={searchPlaceholder}
              defaultValue={search}
              onChange={(e) => handleSearch(e.target.value)}
              className="w-full text-sm sm:text-base"
            />
          )}
        </div>

        {/* Desktop Filters */}
        {(filters.length > 0 || customFilters) && (
          <div className="hidden md:block">{renderDesktopFilters()}</div>
        )}
      </div>

      {/* Table Header - Desktop only */}
      <div className="hidden md:flex justify-between items-center bg-gray-100 text-black p-2 rounded-lg">
        {headers.map((header, index) => (
          <div
            key={index}
            className={header.className || `w-1/${headers.length}`}
          >
            {header.label}
          </div>
        ))}
      </div>

      {/* Cards */}
      {data && data.records && data.records.length > 0 ? (
        data.records.map((item, index) => {
          const cardData = renderCard(item);

          return (
            <Card key={index} className="bg-white gap-0 py-2">
              <CardHeader className="px-4">
                <p className="uppercase text-sm md:text-base">
                  {cardData.title}
                </p>
                {cardData.subtitle && (
                  <p className="uppercase text-xs text-wrap">
                    {cardData.subtitle}
                  </p>
                )}
              </CardHeader>
              <CardContent className="flex flex-col md:flex-row md:justify-between items-start md:items-center px-2 md:px-4 gap-2 md:gap-0">
                {/* Content */}
                <div className="w-full md:flex-1">{cardData.content}</div>

                {/* Status */}
                {cardData.status && (
                  <div className="w-full md:w-auto flex justify-between md:justify-center mt-2 md:mt-0">
                    <Badge
                      variant={cardData.status.variant}
                      className="text-xs sm:text-sm"
                    >
                      {cardData.status.label}
                    </Badge>
                  </div>
                )}

                {/* Actions */}
                <div className="w-full md:w-auto flex justify-end gap-1 sm:gap-2 mt-2 md:mt-0 overflow-x-auto">
                  <TooltipProvider>
                    {cardData.actions.map((action, actionIndex) => (
                      <Tooltip key={actionIndex}>
                        <TooltipTrigger asChild>
                          <Button
                            size="sm"
                            variant={action.variant || "default"}
                            onClick={action.onClick}
                            disabled={action.disabled}
                            className={action.className}
                          >
                            {action.icon}
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                          <p>{action.tooltip}</p>
                        </TooltipContent>
                      </Tooltip>
                    ))}
                  </TooltipProvider>
                </div>
              </CardContent>
            </Card>
          );
        })
      ) : (
        <p className="flex items-center justify-center gap-2">
          <Inbox size={24} /> {emptyStateMessage}
        </p>
      )}

      {/* Pagination */}
      {data && data.total_pages > 1 && (
        <Pagination
          currentPage={page}
          totalPages={data?.total_pages || 1}
          onPageChange={handlePageChange}
          t={(key) => key}
          showPageNumbers={true}
        />
      )}
    </div>
  );
}
