import { useInfiniteQuery } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { HiChevronDown } from "react-icons/hi";
import * as React from "react";
import { Button } from "./ui/button";
import { Input } from "./ui/input";

interface InfiniteScrollSelectProps<T> {
  fetchData: (params: {
    page: number;
    search?: string;
  }) => Promise<{ items: T[]; nextPage: number | null }>;
  queryKey: string[];
  renderItem: (item: T) => string;
  getItemValue: (item: T) => string;
  placeholder?: string;
  onValueChange?: (value: string) => void;
  onItemSelect?: (item: T) => void;
  value?: string;
  defaultLabel?: string;
  multiple?: boolean;
  maxVisibleItems?: number;
  onMultipleValueChange?: (values: string[]) => void;
  onMultipleItemSelect?: (items: T[]) => void;
  values?: string[];
  disabledItems?: (string | number)[];
}

export function InfiniteScrollSelect<T>({
  fetchData,
  queryKey,
  renderItem,
  getItemValue,
  placeholder = "Chọn...",
  onValueChange,
  onItemSelect,
  value,
  defaultLabel,
  multiple = false,
  maxVisibleItems,
  onMultipleValueChange,
  onMultipleItemSelect,
  values = [],
  disabledItems,
}: InfiniteScrollSelectProps<T>) {
  const [search, setSearch] = React.useState("");
  const [debouncedSearch, setDebouncedSearch] = React.useState("");
  const [isOpen, setIsOpen] = React.useState(false);
  const [selectedLabel, setSelectedLabel] = React.useState<string | null>(
    defaultLabel || null
  );
  const [selectedValues, setSelectedValues] = React.useState<string[]>(values);

  const wrapperRef = React.useRef<HTMLDivElement | null>(null);
  const scrollRef = React.useRef<HTMLDivElement | null>(null);

  React.useEffect(() => {
    if (defaultLabel) {
      setSelectedLabel(defaultLabel);
    }
  }, [defaultLabel]);

  React.useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(search);
    }, 500);
    return () => clearTimeout(timer);
  }, [search]);

  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading } =
    useInfiniteQuery({
      queryKey: [...queryKey, debouncedSearch],
      queryFn: ({ pageParam = 1 }) =>
        fetchData({ page: pageParam, search: debouncedSearch }),
      initialPageParam: 1,
      getNextPageParam: (lastPage) => lastPage.nextPage,
      staleTime: 5 * 60 * 1000,
    });

  const items = React.useMemo(
    () => data?.pages.flatMap((page) => page.items) ?? [],
    [data]
  );

  React.useEffect(() => {
    if (!scrollRef.current || !isOpen || items.length === 0) return;
    const observer = new IntersectionObserver(
      (entries) => {
        const first = entries[0];
        if (first.isIntersecting && hasNextPage && !isFetchingNextPage) {
          fetchNextPage();
        }
      },
      { root: scrollRef.current, rootMargin: "50px" }
    );
    const children = scrollRef.current.children;
    const lastChild = children[children.length - 1];
    if (lastChild) observer.observe(lastChild);
    return () => observer.disconnect();
  }, [isOpen, hasNextPage, isFetchingNextPage, fetchNextPage, items.length]);

  React.useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (
        wrapperRef.current &&
        !wrapperRef.current.contains(e.target as Node)
      ) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  React.useEffect(() => {
    if (!value && !multiple) {
      setSelectedLabel(null);
    }
  }, [value, multiple]);

  React.useEffect(() => {
    if (multiple && JSON.stringify(selectedValues) !== JSON.stringify(values)) {
      setSelectedValues(values);
    }
  }, [values, multiple]);

  const handleSelectChange = (val: string) => {
    const item = items.find((i) => getItemValue(i) === val);
    if (item) {
      setSelectedLabel(renderItem(item));
      onValueChange?.(val);
      onItemSelect?.(item);
    }
    setIsOpen(false);
  };

  const handleMultipleSelectChange = (val: string) => {
    let newSelectedValues: string[];

    if (selectedValues.includes(val)) {
      newSelectedValues = selectedValues.filter((v) => v !== val);
    } else {
      newSelectedValues = [...selectedValues, val];
    }

    setSelectedValues(newSelectedValues);
    onMultipleValueChange?.(newSelectedValues);

    const selectedItems = items.filter((item) =>
      newSelectedValues.includes(getItemValue(item))
    );
    onMultipleItemSelect?.(selectedItems);
  };

  const getMultipleLabel = () => {
    if (selectedValues.length === 0) return placeholder;

    const visibleCount = maxVisibleItems ?? 2;
    const visibleItems = items.filter((i) =>
      selectedValues.includes(getItemValue(i))
    );

    const labels = visibleItems.slice(0, visibleCount).map(renderItem);
    const remaining = selectedValues.length - visibleCount;

    if (remaining > 0) {
      return `${labels.join(", ")} +${remaining} mục khác`;
    }

    return labels.join(", ");
  };

  return (
    <div ref={wrapperRef} className="relative w-full">
      <Button
        onClick={(e) => {
          e.preventDefault();
          setIsOpen((prev) => !prev);
        }}
        variant="outline"
        className="w-full justify-between">
        <span
          className={`truncate ${
            (multiple ? selectedValues.length > 0 : selectedLabel)
              ? "file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground"
              : "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 cursor-pointer"
          }`}>
          {multiple ? getMultipleLabel() : selectedLabel || placeholder}
        </span>
        <HiChevronDown
          className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${
            isOpen ? "rotate-180" : ""
          }`}
        />
      </Button>

      {isOpen && (
        <div className="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
          <div className="p-2 border-b border-gray-100">
            <Input
              type="text"
              placeholder="Tìm kiếm..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>

          <div ref={scrollRef} className="max-h-60 overflow-y-auto text-sm">
            {isLoading ? (
              <div className="flex justify-center items-center p-4 text-gray-500">
                <Loader2 className="h-5 w-5 animate-spin mr-2" />
                Đang tải...
              </div>
            ) : items.length === 0 ? (
              <div className="text-center text-gray-400 py-4">
                Không có dữ liệu
              </div>
            ) : (
              <>
                {items.map((item) => {
                  const itemValue = getItemValue(item);
                  const isSelected = multiple
                    ? selectedValues.includes(itemValue)
                    : value === itemValue;
                  const isDisabled = disabledItems
                    ? !disabledItems.map(String).includes(itemValue)
                    : false;

                  return (
                    <div
                      key={itemValue}
                      onClick={() => {
                        if (isDisabled) return;
                        multiple
                          ? handleMultipleSelectChange(itemValue)
                          : handleSelectChange(itemValue);
                      }}
                      className={`cursor-pointer px-3 py-2 transition flex items-center gap-2
        ${isDisabled ? "opacity-50 cursor-not-allowed" : "hover:bg-blue-50"}
        ${isSelected ? "bg-blue-100 font-semibold" : ""}
      `}>
                      {multiple && (
                        <input
                          type="checkbox"
                          checked={isSelected}
                          disabled={isDisabled}
                          onChange={() => {}}
                          className="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                        />
                      )}
                      <span className="flex-1">{renderItem(item)}</span>
                    </div>
                  );
                })}

                {isFetchingNextPage && (
                  <div className="flex justify-center p-3">
                    <Loader2 className="h-5 w-5 animate-spin" />
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
