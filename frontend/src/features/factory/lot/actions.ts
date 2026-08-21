import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  ApiResponseListV2,
  CommonPaginationParams,
} from "@/types/api";
import { IProductLot, IProductLotData, IProductLotInventory } from "./types";

export interface IGetProductLotParams extends CommonPaginationParams {
  production_order_id?: number;
  production_date_from?: string;
  production_date_to?: string;
  factory_id?: number;
  status?: "available" | "allocated" | "shipped" | "defective" | "all";
  lot_type?: "internal" | "external" | "all";
}

export interface IGetProductLotInventoryParams extends CommonPaginationParams {
  production_order_id?: number;
  production_date_from?: string;
  production_date_to?: string;
  factory_id?: number;
  eudr_type?: "eudr" | "non_eudr" | "all";
  lot_type?: "internal" | "external" | "all";
}

export const getProductLots = async (
  params: IGetProductLotParams,
): Promise<ApiResponseList<IProductLot[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/`;
    const res = await axiosInstance.get(url, { params });
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const getProductLotByCode = async (
  product_lot_code: string,
): Promise<ApiResponse<IProductLot>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/${product_lot_code}`;
    const res = await axiosInstance.get(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const createProductLot = async (data: IProductLotData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/`;
    const res = await axiosInstance.post(url, data);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const getProductLotById = async (
  product_lot_code: string,
): Promise<ApiResponse<IProductLot>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/${product_lot_code}`;
    const res = await axiosInstance.get(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductLot = async (
  product_lot_code: string,
  data: IProductLotData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/${product_lot_code}`;
    const res = await axiosInstance.put(url, data);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductLot = async (product_lot_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/${product_lot_code}`;
    const res = await axiosInstance.delete(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const inventoryProductLots = async (
  params: IGetProductLotInventoryParams,
): Promise<ApiResponseList<IProductLotInventory[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/inventory/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
