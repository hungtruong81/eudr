import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IProductionOrder, IProductionOrderData } from "./types";
import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";

export interface IGetProductOrderParams extends CommonPaginationParams {
  status?: "approved" | "in_production" | "completed" | "all";
  production_date_from?: string;
  production_date_to?: string;
  created_date_from?: string;
  created_date_to?: string;
}

export const getProductionOrders = async (
  params: Partial<IGetProductOrderParams>,
): Promise<ApiResponseList<IProductionOrder[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-order/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCodeProduction = async () => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-order/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductionOrder = async (data: IProductionOrderData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-order/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductionOrder = async (
  production_order_code: string,
  data: IProductionOrderData,
) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-order/${production_order_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductionOrder = async (production_order_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-order/${production_order_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getProductionOrderById = async (production_order_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-order/${production_order_code}/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
