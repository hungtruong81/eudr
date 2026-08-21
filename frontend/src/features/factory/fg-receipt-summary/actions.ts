import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import { IFgReceiptSummary } from "./types";

export interface IGetFgReceiptSummaryParams extends CommonPaginationParams {
  production_order_id?: number;
  product_tank_id?: number;
  product_type_category?: string;
  product_type_id?: number;
  production_date_from?: string;
  production_date_to?: string;
  status?: "available" | "allocated" | "shipped" | "defective" | "all";
}

export const getFgReceiptSummary = async (
  params: IGetFgReceiptSummaryParams,
): Promise<ApiResponseList<IFgReceiptSummary[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/rubber-blocks/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getFgReceiptSummaryDetail = async (
  rubber_block_code: string,
): Promise<ApiResponse<IFgReceiptSummary>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/rubber-blocks/${rubber_block_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
