export interface CommonPaginationParams {
  page: number;
  limit: number;
  search?: string;
}

export interface ApiResponseList<T> {
  result: boolean;
  data: {
    current_page: number;
    total_pages: number;
    page_limit: number;
    total_records: number;
    records: T;
    items: T;
    data: T;
  };
}

export interface ApiResponseListV2<T> {
  result: boolean;
  current_page: number;
  total_pages: number;
  page_limit: number;
  total_records: number;
  records: T[];
}

export interface ApiResponse<T> {
  result: string;
  data: T;
}
