"use client";
import { useDebounce } from "@/hooks/use-debounce";
import { ApiResponse, ApiResponseList } from "@/types/api";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Select, SelectProps, Spin } from "antd";
import { useMemo, useState } from "react";

const PAGE_SIZE_DEFAULT = 20;

export interface InfiniteScrollSelectProps<T> extends Omit<
  SelectProps,
  "options" | "onSearch" | "loading" | "filterOption"
> {
  queryKey: string[];
  fetchFn: (params: {
    search?: string;
    page: number;
    limit: number;
  }) => Promise<ApiResponseList<T[]>>;
  mapOption: (item: T) => { label: string; value: string };
  debounceMs?: number;
  limit?: number;
  enabled?: boolean;
  maxCount?: number;
  initialOptions?: { label: React.ReactNode; value: string | number }[];
}

export function InfiniteScrollSelect<T>({
  queryKey,
  fetchFn,
  mapOption,
  debounceMs = 300,
  limit = PAGE_SIZE_DEFAULT,
  enabled = true,
  maxCount = 3,
  initialOptions = [],
  ...selectProps
}: InfiniteScrollSelectProps<T>) {
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounce(search, debounceMs);

  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading } =
    useInfiniteQuery({
      queryKey: [...queryKey, { search: debouncedSearch, limit: limit }],
      queryFn: ({ pageParam = 1 }) =>
        fetchFn({
          search: debouncedSearch,
          page: pageParam,
          limit: limit,
        }),
      initialPageParam: 1,
      getNextPageParam: (lastPage, allPages) => {
        const total = lastPage?.data?.total_records ?? 0;
        const loaded = allPages.length * limit;
        return loaded < total ? allPages.length + 1 : undefined;
      },
      refetchOnWindowFocus: false,
      enabled,
    });

  const options = useMemo(() => {
    const fetchedOptions: { label: React.ReactNode; value: string | number }[] = [];
    if (data?.pages) {
      data.pages.forEach((page) => {
        const items = Array.isArray(page?.data?.records)
          ? page?.data?.records
          : [];
        fetchedOptions.push(...items.map(mapOption));
      });
    }

    // Merge logic keeping unique values, initialOptions take precedence if they exist
    const optionsMap = new Map();
    [...initialOptions, ...fetchedOptions].forEach((opt) => {
      if (!optionsMap.has(opt.value)) {
        optionsMap.set(opt.value, opt);
      }
    });

    return Array.from(optionsMap.values());
  }, [data, mapOption, initialOptions]);

  const handlePopupScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const target = e.currentTarget;
    const atBottom =
      target.scrollTop + target.offsetHeight >= target.scrollHeight - 20;
    if (atBottom && hasNextPage && !isFetchingNextPage) {
      fetchNextPage();
    }
  };

  return (
    <Select
      showSearch={{
        onSearch: setSearch,
      }}
      options={options}
      loading={isLoading}
      onPopupScroll={handlePopupScroll}
      {...(selectProps.mode === "multiple" || selectProps.mode === "tags"
        ? { maxCount }
        : {})}
      popupRender={(menu) => (
        <>
          {menu}
          {isFetchingNextPage && (
            <div style={{ textAlign: "center", padding: 8 }}>
              <Spin size="small" />
            </div>
          )}
        </>
      )}
      {...selectProps}
    />
  );
}
